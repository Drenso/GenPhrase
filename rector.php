<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;

return RectorConfig::configure()
  ->withCache('./var/cache/rector', FileCacheStorage::class)
  ->withPaths([__DIR__ . '/src'])
  ->withParallel(timeoutSeconds: 180, jobSize: 10)
  ->withImportNames()
  ->withSkip([
    ReadOnlyPropertyRector::class,
  ])
  ->withPhpSets()
  ->withPreparedSets(
    typeDeclarations: true,
  );
