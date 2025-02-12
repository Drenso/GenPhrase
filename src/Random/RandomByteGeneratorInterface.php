<?php

namespace Drenso\GenPhrase\Random;

interface RandomByteGeneratorInterface
{
  public function getBytes(int $count): string;
}
