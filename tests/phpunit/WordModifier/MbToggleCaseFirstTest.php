<?php

namespace Drenso\GenPhrase\Tests;

use Drenso\GenPhrase\WordModifier\MbToggleCaseFirst;
use PHPUnit\Framework\TestCase;

class GenPhraseWordModifierMbToggleCaseFirstTest extends TestCase
{
  public function testModifyCapitalizes()
  {
    $word     = 'äbcd';
    $expected = 'Äbcd';

    $randomProvider = $this->createMock('Drenso\\GenPhrase\\Random\\Random');
    $randomProvider
      ->expects($this->once())
      ->method('getElement')
      ->willReturn(0);

    $obj  = new MbToggleCaseFirst($randomProvider);
    $test = $obj->modify($word);

    $this->assertEquals($expected, $test);
  }

  public function testModifyLowers()
  {
    $word     = 'Äbcd';
    $expected = 'äbcd';

    $randomProvider = $this->createMock('Drenso\\GenPhrase\\Random\\Random');
    $randomProvider
      ->expects($this->once())
      ->method('getElement')
      ->willReturn(0);

    $obj  = new MbToggleCaseFirst($randomProvider);
    $test = $obj->modify($word);

    $this->assertEquals($expected, $test);
  }
}
