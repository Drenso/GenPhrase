<?php

namespace Drenso\GenPhrase\Tests;

use Drenso\GenPhrase\Password;
use Drenso\GenPhrase\WordlistHandler\Filesystem;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class GenPhrasePasswordTest extends TestCase
{
  public $entropyLowBits = 25;

  public $entropyHighBits = 121;

  public $testWords = ['test', 'test', 'test', 'test', 'test',
    'test', 'test', 'test', 'test', 'test',
    'test', 'test', 'test', 'test', 'test',
    'test', 'test', 'test', 'test', 'test'];

  public $testWordsNonUnique = ['test2', 'test2', 'test2', 'test3', 'test4',
    'test5', 'test6', 'test7', 'test8', 'test9',
    'test10', 'test11', 'test12', 'test12', 'test14',
    'test15', 'test16', 'test17', 'test18', 'test19'];

  public function testConstructWithoutArguments()
  {
    $this->assertInstanceOf('Drenso\\GenPhrase\\Password', new Password());
  }

  public function testGetDefaultSeparators()
  {
    $obj        = new Password();
    $separators = $obj->getSeparators();

    $this->assertEquals('-_!$&*+=23456789', $separators);
  }

  public function testCanSetSeparators()
  {
    $newSeparators = '1234';
    $obj           = new Password();
    $obj->setSeparators($newSeparators);

    $this->assertEquals($newSeparators, $obj->getSeparators());
  }

  public function testGetDefaultEncoding()
  {
    $obj = new Password();

    $this->assertEquals('utf-8', $obj->getEncoding());
  }

  public function testCanSetEncoding()
  {
    $newEncoding = 'iso-8859-1';
    $obj         = new Password();
    $obj->setEncoding($newEncoding);

    $this->assertEquals($newEncoding, $obj->getEncoding());
  }

  public function testGetDefaultConstructorDependencies()
  {
    $obj = new Password();

    $this->assertInstanceOf('Drenso\\GenPhrase\\WordlistHandler\\Filesystem', $obj->getWordlistHandler());
    $this->assertInstanceOf('Drenso\\GenPhrase\\WordModifier\\MbToggleCaseFirst', $obj->getWordmodifier());
    $this->assertInstanceOf('Drenso\\GenPhrase\\Random\\Random', $obj->getRandomProvider());
  }

  public function testGenerateReturnsNonEmptyString()
  {
    $obj      = new Password();
    $password = $obj->generate(30);

    $this->assertIsString($password);
    $this->assertGreaterThan(0, strlen($password));
  }

  public function testGenerateWithLowBitsThrowsException()
  {
    $this->expectException(InvalidArgumentException::class);
    $obj = new Password();
    $obj->generate($this->entropyLowBits);
  }

  public function testGenerateWithHighBitsThrowsException()
  {
    $this->expectException(InvalidArgumentException::class);
    $obj = new Password();
    $obj->generate($this->entropyHighBits);
  }

  public function testNotEnoughWordsThrowsException()
  {
    $this->expectException(RuntimeException::class);
    $wordlistHandler = $this->createMock('Drenso\\GenPhrase\\WordlistHandler\\Filesystem');
    $wordlistHandler
      ->expects($this->any())
      ->method('getWordsAsArray')
      ->willReturn(['a']);

    $obj = new Password($wordlistHandler);
    $obj->generate();
  }

  public function testNotEnoughUniqueWordsThrowsException()
  {
    $this->expectException(RuntimeException::class);
    $path            = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'Wordlist' . DIRECTORY_SEPARATOR . 'dublicate_words.lst';
    $wordlistHandler = new Filesystem(['path' => $path, 'identifier' => 'test']);

    $obj = new Password($wordlistHandler);
    $obj->generate();
  }

  public function testGenerateReturnsExpectedStrings()
  {
    $wordlistHandler = $this->createMock('Drenso\\GenPhrase\\WordlistHandler\\Filesystem');
    $wordlistHandler
      ->expects($this->any())
      ->method('getWordsAsArray')
      ->willReturn($this->testWords);

    $wordModifier = $this->createMock('Drenso\\GenPhrase\\WordModifier\\MbToggleCaseFirst');
    $wordModifier
      ->expects($this->any())
      ->method('modify')
      ->willReturn('test');
    $wordModifier
      ->expects($this->any())
      ->method('getWordCountMultiplier')
      ->willReturn(1);

    $randomProvider = $this->createMock('Drenso\\GenPhrase\\Random\\Random');
    $randomProvider
      ->expects($this->any())
      ->method('getElement')
      ->willReturn(0);

    $obj = new Password($wordlistHandler, $wordModifier, $randomProvider);
    $obj->disableSeparators(true);

    $password = $obj->generate(26);
    $this->assertEquals('test test test test test test test', $password);

    $password = $obj->generate(36);
    $this->assertEquals('test test test test test test test test test', $password);

    $password = $obj->generate(50);
    $this->assertEquals('test test test test test test test test test test test test', $password);
  }

  public static function makesSenseToUseSeparatorsDataProvider()
  {
    return [
      [26, 13, 4, false],
      [27, 13, 4, true],
      [28, 13, 4, true],
      [29, 13, 4, true],
      [30, 13, 4, true],
      [31, 13, 4, false],
      [32, 13, 4, false],
      [33, 13, 4, false],
      [34, 13, 4, false],
      [35, 13, 4, false],
      [36, 13, 4, false],
      [37, 13, 4, false],
    ];
  }

  #[DataProvider('makesSenseToUseSeparatorsDataProvider')]
  public function testMakesSenseToUseSeparators($bits, $wordBits, $separatorBits, $shouldUse)
  {
    $obj = new Password();

    $this->assertEquals($shouldUse, $obj->makesSenseToUseSeparators($bits, $wordBits, $separatorBits), 'Failed for bits:' . $bits);
  }

  public function testAlwaysUseSeparators()
  {
    $wordlistHandler = $this->createMock('Drenso\\GenPhrase\\WordlistHandler\\Filesystem');
    $wordlistHandler
      ->expects($this->any())
      ->method('getWordsAsArray')
      ->willReturn($this->testWords);

    $wordModifier = $this->createMock('Drenso\\GenPhrase\\WordModifier\\MbToggleCaseFirst');
    $wordModifier
      ->expects($this->any())
      ->method('modify')
      ->willReturn('test');
    $wordModifier
      ->expects($this->any())
      ->method('getWordCountMultiplier')
      ->willReturn(1);

    $randomProvider = $this->createMock('Drenso\\GenPhrase\\Random\\Random');
    $randomProvider
      ->expects($this->any())
      ->method('getElement')
      ->willReturn(0);

    $obj = new Password($wordlistHandler, $wordModifier, $randomProvider);
    $obj->setSeparators('$');

    $obj->alwaysUseSeparators(true);
    $password = $obj->generate(26);
    $this->assertEquals('test$test$test$test$test$test$test', $password);

    $obj->alwaysUseSeparators(false);
    $password = $obj->generate(26);
    $this->assertEquals('test test test test test test test', $password);
  }

  public function testSeparatorsAreUnique()
  {
    $obj = new Password();

    $obj->setSeparators('$$');
    $this->assertEquals('$', $obj->getSeparators());

    $obj->setSeparators('112334566');
    $this->assertEquals('123456', $obj->getSeparators());
  }

  public function testEmptySeparatorsThowsException()
  {
    $this->expectException(InvalidArgumentException::class);
    $obj = new Password();

    $obj->setSeparators('');
    $obj->generate();
  }

  public static function precisionFloatIsNotRoundingDataProvider()
  {
    return [
      [log(49667, 2), 15.59],
      [log(99334, 2), 16.59],
      [log(102837, 2), 16.64],
    ];
  }

  #[DataProvider('precisionFloatIsNotRoundingDataProvider')]
  public function testPrecisionFloatIsNotRounding($precision, $expectedValue)
  {
    $obj   = new Password();
    $float = $obj->precisionFloat($precision);

    $this->assertEquals($expectedValue, $float, 'Failed for num: ' . $precision);
  }
}
