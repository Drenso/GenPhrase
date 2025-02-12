<?php

namespace Drenso\GenPhrase\Random;

interface RandomByteGeneratorInterface
{
  /** @param int<1, max> $count */
  public function getBytes(int $count): string;
}
