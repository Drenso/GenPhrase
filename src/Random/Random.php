<?php

namespace Drenso\GenPhrase\Random;

use InvalidArgumentException;
use RangeException;
use RuntimeException;

/**
 * @author timoh <timoh6@gmail.com>
 */
class Random implements RandomInterface
{
  public const int MAX_ALLOWED_POOL_SIZE    = 1048576;
  public const int MAX_ALLOWED_POWER_OF_TWO = 67108864;

  /** Must be <= $powerOfTwo. See below. */
  protected int $maxPoolSize = 1048576;

  /**
   * Must be a power of two, for example 2^26 (67 108 864). In the security
   * point of view, must be >= $maxPoolSize. In the efficiency point of view,
   * should be considerably greater than $maxPoolSize. As we default to
   * 1048576 $maxPoolSize (which should be now finally easily enough for wordlists), using
   * 2^26 as our $powerOfTwo should be enough to keep the probability of
   * having to throw "intermediate" results away low.
   */
  protected int $powerOfTwo = 67108864;

  public function __construct(
    protected readonly RandomByteGeneratorInterface $randomByteGenerator = new RandomByteGenerator(),
  ) {
    $this->checkPowerOfTwo();
  }

  /**
   * Return an element (integer, in range 0-$poolSize minus one) from the
   * given "pool".
   *
   * The element is chosen uniformly at random.
   *
   * If $poolSize is 2: return 0 or 1.
   * If $poolSize is 3: return 0 or 1 or 2.
   * If $poolSize is 4: return 0 or 1 or 2 or 3.
   * etc.
   *
   * @param int $poolSize size of the pool to choose from
   *
   * @throws InvalidArgumentException if provided $poolSize is not between 2 and $maxPoolSize
   * @throws RangeException           if the supplied range is too great to generate
   * @throws RuntimeException         if it was not possible to generate random bytes
   *
   * @return int the generated random number within the pool size
   */
  public function getElement(int $poolSize): int
  {
    /**
     * The general formulation to choose a random element is to find the
     * smallest integer k, such that 2^k >= $poolSize. Then generate a k-bit
     * random number ($result). If $result >= $poolSize, generate a new
     * k-bit random number. Repeat until $result < $poolsize.
     *
     * getElement() uses the "modulo trick" described by Ferguson, Schneier
     * and Kohno. Which reduces the probability of having to throw the
     * intermediate result away (the case where $result >= $poolSize).
     */
    if ($poolSize < 2 || $poolSize > $this->maxPoolSize) {
      throw new InvalidArgumentException('$poolSize must be between 2 and ' . $this->maxPoolSize);
    }

    // Floor it by casting to int.
    $q     = (int)($this->powerOfTwo / $poolSize);
    $range = $poolSize * $q - 1;

    if ($range > PHP_INT_MAX || is_float($range)) {
      throw new RangeException('The supplied range is too great to generate');
    }

    // Floor it by casting to int.
    $bits = (int)log($range, 2) + 1;

    $bytes = (int)max(ceil($bits / 8), 1);
    $mask  = 2 ** $bits - 1;
    /*
     * We borrow here the "mask trick" from PHP-CryptLib, see:
     * https://github.com/ircmaxell/PHP-CryptLib
     * The comment below is from PHP-CryptLib:
     *
     * The mask is a better way of dropping unused bits. Basically what it
     * does is to set all the bits in the mask to 1 that we may need. Since
     * the max range is PHP_INT_MAX, we will never need negative numbers
     * (which would have the MSB set on the max int possible to generate).
     * Therefore we can just mask that away. Since pow returns a float, we
     * need to cast it back to an int so the mask will work.
     *
     * On a 64 bit platform, that means that PHP_INT_MAX is 2^63 - 1. Which
     * is also the mask if 63 bits are needed (by the log(range, 2) call).
     * So if the computed result is negative (meaning the 64th bit is set),
     * the mask will correct that.
     *
     * This turns out to be slightly better than the shift as we don't need
     * to worry about "fixing" negative values.
     */
    do {
      $result = hexdec(bin2hex($this->randomByteGenerator->getBytes($bytes))) & $mask;
    } while ($result > $range);

    return $result % $poolSize;
  }

  /** @throws InvalidArgumentException if $maxPoolSize is greater than $powerOfTwo or supplied $powerOfTwo is not a power of two or if either $powerOfTwo or $maxPoolSize is greater than their allowed max size */
  public function checkPowerOfTwo(?int $powerOfTwo = null, ?int $maxPoolSize = null): bool
  {
    $maxPoolSize ??= $this->maxPoolSize;
    $powerOfTwo  ??= $this->powerOfTwo;

    if ($maxPoolSize > $powerOfTwo) {
      throw new InvalidArgumentException('$powerOfTwo must be >= $maxPoolSize');
    }

    if ($maxPoolSize > self::MAX_ALLOWED_POOL_SIZE) {
      throw new InvalidArgumentException('$maxPoolSize can not be greater than ' . self::MAX_ALLOWED_POOL_SIZE);
    }

    if ($powerOfTwo > self::MAX_ALLOWED_POWER_OF_TWO) {
      throw new InvalidArgumentException('$powerOfTwo can not be greater than ' . self::MAX_ALLOWED_POWER_OF_TWO);
    }

    $isPowerOfTwo = $powerOfTwo && !($powerOfTwo & ($powerOfTwo - 1));
    if ($isPowerOfTwo === false) {
      throw new InvalidArgumentException('Supplied $powerOfTwo is not a power of two');
    }

    return true;
  }

  public function setPowerOfTwo(int $powerOfTwo): void
  {
    $this->checkPowerOfTwo($powerOfTwo);
    $this->powerOfTwo = $powerOfTwo;
  }

  public function setMaxPoolSize(int $maxPoolSize): void
  {
    $this->checkPowerOfTwo(null, $maxPoolSize);
    $this->maxPoolSize = $maxPoolSize;
  }
}
