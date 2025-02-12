<?php

namespace Drenso\GenPhrase\Tests;

use Drenso\GenPhrase\Random\Random;
use Drenso\GenPhrase\Tests\Mock\MockRandomBytes;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RandomTest extends TestCase
{
  public function testTooLowPoolSizeThrowsException(): void
  {
    $this->expectException(InvalidArgumentException::class);
    $obj = new Random();

    $obj->getElement(1);
  }

  public function testTooHighPoolSizeThrowsException(): void
  {
    $this->expectException(InvalidArgumentException::class);
    $obj = new Random();

    $obj->getElement(1048577);
  }

  public function testGetElementGivesUniformDistribution(): void
  {
    $poolSize = 7776;
    $elements = [];

    $randomByteGenerator = new MockRandomBytes();
    $obj                 = new Random($randomByteGenerator);
    $obj->setMaxPoolSize($poolSize);
    $obj->setPowerOfTwo(8192);

    for ($i = 0; $i < $poolSize; $i++) {
      $element = $obj->getElement($poolSize);
      if (!isset($elements[$element])) {
        $elements[$element] = 0;
      } else {
        $elements[$element]++;
      }
    }

    $this->assertCount(1, array_unique($elements));
    $this->assertEquals($poolSize, count($elements));
  }

  public function testInvalidPowerOfTwoThrowsException(): void
  {
    $this->expectException(InvalidArgumentException::class);
    $obj = new Random();

    $obj->setPowerOfTwo(8);
  }

  public function testSetTooHighPowerOfTwoThrowsException(): void
  {
    $this->expectException(InvalidArgumentException::class);
    $obj = new Random();

    $obj->setPowerOfTwo(67108865);
  }

  public function testSetTooHighMaxPoolSizeThrowsException(): void
  {
    $this->expectException(InvalidArgumentException::class);
    $obj = new Random();

    $obj->setMaxPoolSize(1048577);
  }
}
