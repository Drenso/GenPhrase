<?php

namespace Drenso\GenPhrase\Tests\Mock;

use Drenso\GenPhrase\Random\RandomByteGeneratorInterface;

/**
 * @author timoh <timoh6@gmail.com>
 */
class MockRandomBytes implements RandomByteGeneratorInterface
{
  public static int $number = 0;

  public function getBytes(int $count): string
  {
    $ret = static::$number;
    static::$number++;

    return pack('N*', $ret);
  }
}
