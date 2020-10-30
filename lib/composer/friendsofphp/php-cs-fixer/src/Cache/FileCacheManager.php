<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Cache;

/**
 * Class supports caching information about state of fixing files.
 *
 * Cache is supported only for phar version and version installed via composer.
 *
 * File will be processed by PHP CS Fixer only if any of the following conditions is fulfilled:
 *  - cache is corrupt
 *  - fixer version changed
 *  - rules changed
 *  - file is new
 *  - file changed
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class FileCacheManager implements CacheManagerInterface
{
    /**
     * @var FileHandlerInterface
     */
    private $handler;

    /**
     * @var SignatureInterface
     */
    private $signature;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var bool
     */
    private $isDryRun;

    /**
     * @var DirectoryInterface
     */
    private $cacheDirectory;

    /**
     * @param bool $isDryRun
     */
    public function __construct(
        FileHandlerInterface $handler,
        SignatureInterface $signature,
        $isDryRun = false,
        DirectoryInterface $cacheDirectory = null
    ) {
        $this->handler = $handler;
        $this->signature = $signature;
        $this->isDryRun = $isDryRun;
        $this->cacheDirectory = $cacheDirectory ?: new Directory('');

        $this->readCache();
    }

    public function __destruct()
    {
        $this->writeCache();
    }

    public function needFixing($file, $fileContent)
    {
        $file = $this->cacheDirectory->getRelativePathTo($file);

        return !$this->cache->has($file) || $this->cache->get($file) !== $this->calcHash($fileContent);
    }

    public function setFile($file, $fileContent)
    {
        $file = $this->cacheDirectory->getRelativePathTo($file);

        $hash = $this->calcHash($fileContent);

        if ($this->isDryRun && $this->cache->has($file) && $this->cache->get($file) !== $hash) {
            $this->cache->clear($file);

            return;
        }

        $this->cache->set($file, $hash);
    }

    private function readCache()
    {
        $cache = $this->handler->read();

        if (!$cache || !$this->signature->equals($cache->getSignature())) {
            $cache = new Cache($this->signature);
        }

        $this->cache = $cache;
    }

    private function writeCache()
    {
        $this->handler->write($this->cache);
    }

    private function calcHash($content)
    {
        return crc32($content);
    }
}
