<?php

namespace Drenso\GenPhrase\WordlistHandler;

/**
 * @author timoh <timoh6@gmail.com>
 */
interface WordlistHandlerInterface
{
  /** @return list<string> */
  public function getWordsAsArray(): array;

  public function addWordlist(string $path, string $identifier): static;

  public function removeWordlist(string $identifier): static;
}
