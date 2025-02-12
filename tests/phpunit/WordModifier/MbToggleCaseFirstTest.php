<?php

namespace Drenso\GenPhrase\Tests;

use Drenso\GenPhrase\Random\Random;
use Drenso\GenPhrase\WordModifier\MbToggleCaseFirst;
use PHPUnit\Framework\TestCase;

class MbToggleCaseFirstTest extends TestCase
{
  public function testModifyCapitalizes(): void
  {
    $word     = 'äbcd';
    $expected = 'Äbcd';

    $randomProvider = $this->createMock(Random::class);
    $randomProvider
      ->expects($this->once())
      ->method('getElement')
      ->willReturn(0);

    $obj  = new MbToggleCaseFirst($randomProvider);
    $test = $obj->modify($word);

    $this->assertEquals($expected, $test);
  }

  public function testModifyLowers(): void
  {
    $word     = 'Äbcd';
    $expected = 'äbcd';

    $randomProvider = $this->createMock(Random::class);
    $randomProvider
      ->expects($this->once())
      ->method('getElement')
      ->willReturn(0);

    $obj  = new MbToggleCaseFirst($randomProvider);
    $test = $obj->modify($word);

    $this->assertEquals($expected, $test);
  }
}
