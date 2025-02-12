<?php

namespace Drenso\GenPhrase\Random;

class RandomByteGenerator implements RandomByteGeneratorInterface
{
  public function getBytes(int $count): string
  {
    return random_bytes($count);
  }
}
