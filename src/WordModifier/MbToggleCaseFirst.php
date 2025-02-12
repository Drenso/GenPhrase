<?php

namespace Drenso\GenPhrase\WordModifier;

use Drenso\GenPhrase\Random\Random;
use Drenso\GenPhrase\Random\RandomInterface;
use Exception;

/**
 * @author timoh <timoh6@gmail.com>
 */
readonly class MbToggleCaseFirst implements WordModifierInterface
{
  /**
   * $probabilityPoolSize controls the changes whether to modify the word or
   * not.
   *
   * We fetch a random number from a set size of $probabilityPoolSize, and
   * if this number is 0, then the word will be modified.
   *
   * If $probabilityPoolSize is 2, we fetch a number in the range 0-1, so
   * there is a 1/2 change to modify the word.
   *
   * If $probabilityPoolSize is 3, we fetch a number in the range 0-2, so
   * there is a 1/3 change to modify the word. Etc.
   */
  public function __construct(
    protected RandomInterface $randomProvider = new Random(),
    protected int $probabilityPoolSize = 2,
    protected int $wordCountMultiplier = 2,
  ) {
  }

  /**
   * Performs case folding on the first character of a supplied word (making it either lower or upper case).
   * The word is modified, by default, on a 50:50 chance. I.e. we choose a random number 0 or 1, and if we
   * get 0, we modify the word.
   *
   * @throws Exception
   */
  public function modify(string $string, string $encoding = 'utf-8'): string
  {
    $len    = mb_strlen($string, $encoding);

    if ($len > 0) {
      try {
        if ($this->randomProvider->getElement($this->probabilityPoolSize) === 0) {
          $character = mb_substr($string, 0, 1, $encoding);
          $upper     = mb_strtoupper($character, $encoding);
          $lower     = mb_strtolower($character, $encoding);

          if ($character === $upper) {
            $character = $lower;
          } else {
            $character = $upper;
          }

          $string = $character . mb_substr($string, 1, $len, $encoding);
        }
      } catch (Exception $e) {
        throw $e;
      }
    }

    return $string;
  }

  /** @return int The multiplier */
  public function getWordCountMultiplier(): int
  {
    return $this->wordCountMultiplier;
  }
}
