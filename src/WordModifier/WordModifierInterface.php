<?php

namespace Drenso\GenPhrase\WordModifier;

/**
 * @author timoh <timoh6@gmail.com>
 */
interface WordModifierInterface
{
  public function modify(string $string, string $encoding): string;

  public function getWordCountMultiplier(): int;
}
