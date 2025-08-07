<?php

namespace Drenso\GenPhrase\WordlistHandler;

use RuntimeException;

/**
 * @author timoh <timoh6@gmail.com>
 */
class Filesystem implements WordlistHandlerInterface
{
  /**
   * List of wordlists as a key-value array.
   * E.g. $wordlists['default'] = '/path/to/GenPhrase/Wordlists/english.lst';.
   *
   * @var array<string, string>
   */
  protected array $wordlists = [];

  protected static bool $isCached = false;

  /** @var list<string> */
  protected static array $words = [];

  /**
   * @param array{
   *   path: string,
   *   identifier: string,
   * }|null $wordlist e.g. array('path' => '/some/path/to/wordlist', 'identifier' => 'some_id').
   */
  public function __construct(?array $wordlist = null)
  {
    // Default to english.lst
    if ($wordlist === null) {
      $path       = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Wordlists' . DIRECTORY_SEPARATOR . 'english.lst';
      $identifier = 'default';
    } else {
      $path       = $wordlist['path'];
      $identifier = $wordlist['identifier'];
    }

    $this->addWordlist($path, $identifier);
  }

  /**
   * Returns all the unique lines from a file(s) as a numerically indexed array.
   * E.g. Array([0] => word1 [1] => word2...).
   *
   * @throws RuntimeException
   */
  public function getWordsAsArray(): array
  {
    if (self::$isCached === true) {
      return self::$words;
    }

    self::$words = [];

    foreach ($this->wordlists as $file) {
      if (file_exists($file) && is_readable($file)) {
        $wordSet = $this->readData($file);

        if ($wordSet !== false) {
          self::$words = array_merge(self::$words, $wordSet);
        }
      }
    }
    self::$words = array_values(array_unique(self::$words));

    if (!empty(self::$words)) {
      $this->setIsCached(true);

      return self::$words;
    } else {
      throw new RuntimeException('No wordlists available');
    }
  }

  /**
   * Adds the specified file to the list of wordlists. This file will be
   * identified by $identifier.
   *
   * If $path does not contain directory separator character, the filename
   * will be assumed to be in "Wordlists" directory (GenPhrase/Wordlists).
   *
   * @param string $path       the filesystem path to the file
   * @param string $identifier the identifier to identify this file
   */
  public function addWordlist(string $path, string $identifier): static
  {
    if (!str_contains($path, DIRECTORY_SEPARATOR)) {
      $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Wordlists' . DIRECTORY_SEPARATOR . $path;
    }

    $this->wordlists[$identifier] = $path;
    $this->setIsCached(false);

    return $this;
  }

  public function removeWordlist(string $identifier): static
  {
    if (isset($this->wordlists[$identifier])) {
      unset($this->wordlists[$identifier]);
    }
    $this->setIsCached(false);

    return $this;
  }

  public function setIsCached(bool $isCached): static
  {
    self::$isCached = $isCached;

    return $this;
  }

  /** @return false|list<string> */
  protected function readData(string $file): array|false
  {
    return file($file, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
  }
}
