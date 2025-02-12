<?php

namespace Drenso\GenPhrase;

use Drenso\GenPhrase\Random\Random;
use Drenso\GenPhrase\Random\RandomInterface;
use Drenso\GenPhrase\WordlistHandler\Filesystem as WordlistHandler;
use Drenso\GenPhrase\WordlistHandler\WordlistHandlerInterface;
use Drenso\GenPhrase\WordModifier\MbToggleCaseFirst as WordModifier;
use Drenso\GenPhrase\WordModifier\WordModifierInterface;
use Exception;
use InvalidArgumentException;
use RangeException;
use RuntimeException;

/**
 * The Password class clues together all the needed pieces and generates
 * passphrases based on supplied variables.
 *
 * The base logic in this class is adapted from passwdqc's pwqgen program,
 * copyright (c) 2000-2002,2005,2008,2010 by Solar Designer. See:
 * http://www.openwall.com/passwdqc/
 *
 * @author timoh <timoh6@gmail.com>
 */
class Password
{
  /** @var string The separator characters. Must be single-byte characters. */
  protected string $separators = '-_!$&*+=23456789';

  /** @var bool whether to _always_ use separator characters or not (even if using them would not "make sense") */
  protected bool $alwaysUseSeparators = false;

  /** @var bool whether to disable the use of separator characters or not */
  protected bool $disableSeparators = false;

  /** @var bool Whether to disable "word mangling" or not. I.e. to disable capitalization. */
  protected bool $disableWordModifier = false;

  /** @var string character encoding for String functions (for mb_ functions by default) */
  protected string $encoding          = 'utf-8';
  public const int   MIN_WORD_COUNT   = 2;
  public const float MIN_ENTROPY_BITS = 26.0;
  public const float MAX_ENTROPY_BITS = 120.0;

  public function __construct(
    protected readonly WordlistHandlerInterface $wordlistHandler = new WordlistHandler(),
    protected readonly WordModifierInterface $wordModifier = new WordModifier(),
    protected readonly RandomInterface $randomProvider = new Random(),
  ) {
  }

  /**
   * Generates a passphrase based on supplied wordlists, separators, entropy
   * bits and word modifier.
   *
   * @throws InvalidArgumentException
   * @throws RuntimeException
   * @throws RangeException
   */
  public function generate(float $bits = 50.0): string
  {
    $bits          = (float)$bits;
    $separators    = $this->getSeparators();
    $separatorBits = $this->precisionFloat(log(strlen($separators), 2));
    $passPhrase    = '';

    try {
      if ($bits < self::MIN_ENTROPY_BITS || $bits > self::MAX_ENTROPY_BITS) {
        throw new InvalidArgumentException('Invalid parameter: $bits must be between ' . self::MIN_ENTROPY_BITS . ' and ' . self::MAX_ENTROPY_BITS);
      }

      $words = $this->wordlistHandler->getWordsAsArray();
      $count = count($words);
      if ($count < self::MIN_WORD_COUNT) {
        throw new RuntimeException('Wordlist must have at least ' . self::MIN_WORD_COUNT . ' unique words');
      }

      $countForBits = $count;
      if ($this->disableWordModifier !== true) {
        $countForBits = $countForBits * $this->wordModifier->getWordCountMultiplier();
      }
      $wordBits = $this->precisionFloat(log($countForBits, 2));

      if ($wordBits < 1) {
        throw new RuntimeException('Words does not have enough bits to create a passphrase');
      }

      $maxIndex = $count;

      if ($this->disableSeparators === true) {
        $useSeparators = false;
      } elseif ($this->alwaysUseSeparators) {
        $useSeparators = true;
      } else {
        $useSeparators = $this->makesSenseToUseSeparators($bits, $wordBits, $separatorBits);
      }

      do {
        $index = $this->randomProvider->getElement($maxIndex);
        $word  = $words[$index];

        if ($this->disableWordModifier !== true) {
          $word = $this->wordModifier->modify($word, $this->encoding);
        }

        $passPhrase .= $word;
        $bits -= $wordBits;

        if ($bits > $separatorBits && $useSeparators === true && isset($separators[0])) {
          // At least two separator characters
          if (isset($separators[1])) {
            $passPhrase .= $separators[$this->randomProvider->getElement(strlen($separators))];
            $bits -= $separatorBits;
          } else {
            $passPhrase .= $separators[0];
          }
        } elseif ($bits > 0.0) {
          $passPhrase .= ' ';
        }
      } while ($bits > 0.0);
    } catch (Exception $e) {
      throw $e;
    }

    return $passPhrase;
  }

  public function addWordlist(string $path, string $identifier): void
  {
    $this->wordlistHandler->addWordlist($path, $identifier);
  }

  public function removeWordlist(string $identifier): void
  {
    $this->wordlistHandler->removeWordlist($identifier);
  }

  /** @throws InvalidArgumentException */
  public function getSeparators(): string
  {
    $separators                 = $this->separators;
    $separator_characters_array = [];
    $length                     = strlen($separators);

    for ($i = 0; $i < $length; $i++) {
      $separator_characters_array[] = $separators[$i];
    }

    $separator_characters_array = array_values(array_unique($separator_characters_array));
    $separators_string          = implode('', $separator_characters_array);

    if (strlen($separators_string) > 0) {
      return $separators_string;
    } else {
      throw new InvalidArgumentException('Separator characters must contain at least one unique character.');
    }
  }

  /**
   * Sets the separator characters.
   * Must be unique single-byte characters.
   * I.e. setSeparators('123456789-').
   */
  public function setSeparators(string $separators): void
  {
    $this->separators = $separators;
  }

  /** Sets whether to use separators regardless of makesSenseToUseSeparators. */
  public function alwaysUseSeparators(bool $alwaysUseSeparators): void
  {
    $this->alwaysUseSeparators = $alwaysUseSeparators;
  }

  /** Sets whether to use separator characters or not. */
  public function disableSeparators(bool $disableSeparators): void
  {
    $this->disableSeparators = $disableSeparators;
  }

  /** Sets whether to use word modifier or not. */
  public function disableWordModifier(bool $disableWordModifier): void
  {
    $this->disableWordModifier = $disableWordModifier;
  }

  public function getEncoding(): string
  {
    return $this->encoding;
  }

  /** The encoding identifier, for example: ISO-8859-1. */
  public function setEncoding(string $encoding): void
  {
    $this->encoding = $encoding;
  }

  /** Detects whether it is sensible to use separator characters. */
  public function makesSenseToUseSeparators(float $bits, float $wordBits, float $separatorBits): bool
  {
    $wordCount = 1 + ($bits + (($wordBits + $separatorBits - 1) - $wordBits)) / ($wordBits + $separatorBits);

    return (int)(($bits + ($wordBits - 1)) / $wordBits) !== (int)$wordCount;
  }

  public function getWordlistHandler(): WordlistHandlerInterface
  {
    return $this->wordlistHandler;
  }

  public function getWordModifier(): WordModifierInterface
  {
    return $this->wordModifier;
  }

  public function getRandomProvider(): RandomInterface
  {
    return $this->randomProvider;
  }

  /**
   * Returns a float presenting the supplied number.
   *
   * We use BC Math to avoid rounding errors. We use max. 2 digit precision.
   * This is because we do not want to take changes that the returned float
   * will be rounded up.
   *
   * E.g. precisionFloat(log(49667, 2)) will return 15.59 instead
   * of 15.6.
   */
  public function precisionFloat(int|float $num): float
  {
    return (float)bcadd((string)$num, '0', 2);
  }
}
