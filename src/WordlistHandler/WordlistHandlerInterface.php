<?php

namespace Drenso\GenPhrase\WordlistHandler;

/**
 * @author timoh <timoh6@gmail.com>
 */
interface WordlistHandlerInterface
{
  public function getWordsAsArray(): array;

  public function addWordlist(string $path, string $identifier): void;

  public function removeWordlist(string $identifier): void;
}
