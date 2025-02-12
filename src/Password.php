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
  /** @var WordlistHandlerInterface */
  protected $_wordlistHandler;

  /** @var WordModifierInterface */
  protected $_wordModifier;

  /** @var RandomInterface */
  protected $_randomProvider;

  /** @var string The separator characters. Must be single-byte characters. */
  protected $_separators = '-_!$&*+=23456789';

  /** @var bool whether to _always_ use separator characters or not (even if using them would not "make sense") */
  protected $_alwaysUseSeparators = false;

  /** @var bool whether to disable the use of separator characters or not */
  protected $_disableSeparators = false;

  /** @var bool Whether to disable "word mangling" or not. I.e. to disable capitalization. */
  protected $_disableWordModifier = false;

  /** @var string character encoding for String functions (for mb_ functions by default) */
  protected $_encoding          = 'utf-8';
  public const MIN_WORD_COUNT   = 2;
  public const MIN_ENTROPY_BITS = 26.0;
  public const MAX_ENTROPY_BITS = 120.0;

  public function __construct(?WordlistHandlerInterface $wordlistHandler = null,
    ?WordModifierInterface $wordModifier = null,
    ?RandomInterface $randomProvider = null)
  {
    if ($wordlistHandler === null) {
      $wordlistHandler = new WordlistHandler();
    }
    $this->_wordlistHandler = $wordlistHandler;

    if ($wordModifier === null) {
      $wordModifier = new WordModifier();
    }
    $this->_wordModifier = $wordModifier;

    if ($randomProvider === null) {
      $randomProvider = new Random();
    }
    $this->_randomProvider = $randomProvider;
  }

  /**
   * Generates a passphrase based on supplied wordlists, separators, entropy
   * bits and word modifier.
   *
   * @param float $bits
   *
   * @throws InvalidArgumentException
   * @throws RuntimeException
   * @throws RangeException
   *
   * @return string
   */
  public function generate($bits = 50.0)
  {
    $bits          = (float)$bits;
    $separators    = $this->getSeparators();
    $separatorBits = $this->precisionFloat(log(strlen($separators), 2));
    $passPhrase    = '';

    try {
      if ($bits < self::MIN_ENTROPY_BITS || $bits > self::MAX_ENTROPY_BITS) {
        throw new InvalidArgumentException('Invalid parameter: $bits must be between ' . self::MIN_ENTROPY_BITS . ' and ' . self::MAX_ENTROPY_BITS);
      }

      $words = $this->_wordlistHandler->getWordsAsArray();
      $count = count($words);
      if ($count < self::MIN_WORD_COUNT) {
        throw new RuntimeException('Wordlist must have at least ' . self::MIN_WORD_COUNT . ' unique words');
      }

      $countForBits = $count;
      if ($this->_disableWordModifier !== true) {
        $countForBits = $countForBits * $this->_wordModifier->getWordCountMultiplier();
      }
      $wordBits = $this->precisionFloat(log($countForBits, 2));

      if ($wordBits < 1) {
        throw new RuntimeException('Words does not have enough bits to create a passphrase');
      }

      $maxIndex = $count;

      if ($this->_disableSeparators === true) {
        $useSeparators = false;
      } elseif ($this->_alwaysUseSeparators) {
        $useSeparators = true;
      } else {
        $useSeparators = $this->makesSenseToUseSeparators($bits, $wordBits, $separatorBits);
      }

      do {
        $index = $this->_randomProvider->getElement($maxIndex);
        $word  = $words[$index];

        if ($this->_disableWordModifier !== true) {
          $word = $this->_wordModifier->modify($word, $this->_encoding);
        }

        $passPhrase .= $word;
        $bits -= $wordBits;

        if ($bits > $separatorBits && $useSeparators === true && isset($separators[0])) {
          // At least two separator characters
          if (isset($separators[1])) {
            $passPhrase .= $separators[$this->_randomProvider->getElement(strlen($separators))];
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

  /**
   * @param string $path
   * @param string $identifier
   */
  public function addWordlist($path, $identifier)
  {
    $this->_wordlistHandler->addWordlist($path, $identifier);
  }

  /** @param string $identifier */
  public function removeWordlist($identifier)
  {
    $this->_wordlistHandler->removeWordlist($identifier);
  }

  /**
   * @throws InvalidArgumentException
   *
   * @return string
   */
  public function getSeparators()
  {
    $separators                 = $this->_separators;
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
   *
   * @param string $separators
   */
  public function setSeparators($separators)
  {
    $this->_separators = (string)$separators;
  }

  /**
   * Sets whether to use separators regardless of makesSenseToUseSeparators.
   *
   * @param bool $alwaysUseSeparators
   */
  public function alwaysUseSeparators($alwaysUseSeparators)
  {
    $this->_alwaysUseSeparators = (bool)$alwaysUseSeparators;
  }

  /**
   * Sets whether to use separator characters or not.
   *
   * @param bool $disableSeparators
   */
  public function disableSeparators($disableSeparators)
  {
    $this->_disableSeparators = (bool)$disableSeparators;
  }

  /**
   * Sets whether to use word modifier or not.
   *
   * @param bool $disableWordModifier
   */
  public function disableWordModifier($disableWordModifier)
  {
    $this->_disableWordModifier = (bool)$disableWordModifier;
  }

  /** @return string $this->_encoding */
  public function getEncoding()
  {
    return $this->_encoding;
  }

  /**
   * The encoding identifier, for example: ISO-8859-1.
   *
   * @param string $encoding
   */
  public function setEncoding($encoding)
  {
    $this->_encoding = $encoding;
  }

  /**
   * Detects whether it is sensible to use separator characters.
   *
   * @param float $bits
   * @param float $wordBits
   * @param float $separatorBits
   *
   * @return bool
   */
  public function makesSenseToUseSeparators($bits, $wordBits, $separatorBits)
  {
    $wordCount = 1 + ($bits + (($wordBits + $separatorBits - 1) - $wordBits)) / ($wordBits + $separatorBits);

    return (int)(($bits + ($wordBits - 1)) / $wordBits) !== (int)$wordCount;
  }

  /** @return WordlistHandlerInterface */
  public function getWordlistHandler()
  {
    return $this->_wordlistHandler;
  }

  /** @return WordModifierInterface */
  public function getWordModifier()
  {
    return $this->_wordModifier;
  }

  /** @return Random */
  public function getRandomProvider()
  {
    return $this->_randomProvider;
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
   *
   * @param int|float $num
   *
   * @return float
   */
  public function precisionFloat($num)
  {
    return (float)bcadd($num, '0', 2);
  }
}
