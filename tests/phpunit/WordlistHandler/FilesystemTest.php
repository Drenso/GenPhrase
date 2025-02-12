<?php

namespace Drenso\GenPhrase\Tests;

use Drenso\GenPhrase\WordlistHandler\Filesystem;
use PHPUnit\Framework\TestCase;

class FilesystemTest extends TestCase
{
  public function testContainsNoDuplicates(): void
  {
    $path = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'wordlists' . DIRECTORY_SEPARATOR . 'dublicate_words.lst';
    $obj  = new Filesystem(['path' => $path, 'identifier' => 'test']);

    $returnedWords = $obj->getWordsAsArray();

    $this->assertCount(3, $returnedWords);
  }

  public function testCanAddWordlist(): void
  {
    $path  = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'wordlists' . DIRECTORY_SEPARATOR . 'dublicate_words.lst';
    $path2 = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'wordlists' . DIRECTORY_SEPARATOR . 'two_words.lst';
    $obj   = new Filesystem(['path' => $path, 'identifier' => 'test']);
    $obj->addWordlist($path2, 'test2');

    $returnedWords = $obj->getWordsAsArray();

    $this->assertCount(5, $returnedWords);
  }
}
