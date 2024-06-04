<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
class OC_FileChunking {
	protected $info;
	protected $cache;

	/**
	 * TTL of chunks
	 *
	 * @var int
	 */
	protected $ttl;

	public static function decodeName($name) {
		preg_match('/(?P<name>.*)-chunking-(?P<transferid>\d+)-(?P<chunkcount>\d+)-(?P<index>\d+)/', $name, $matches);
		return $matches;
	}

	/**
	 * @param string[] $info
	 */
	public function __construct($info) {
		$this->info = $info;
		$this->ttl = \OC::$server->getConfig()->getSystemValueInt('cache_chunk_gc_ttl', 86400);
	}

	public function getPrefix() {
		$name = $this->info['name'];
		$transferid = $this->info['transferid'];

		return $name.'-chunking-'.$transferid.'-';
	}

	protected function getCache() {
		if (!isset($this->cache)) {
			$this->cache = new \OC\Cache\File();
		}
		return $this->cache;
	}

	/**
	 * Stores the given $data under the given $key - the number of stored bytes is returned
	 *
	 * @param string $index
	 * @param resource $data
	 * @return int
	 */
	public function store($index, $data) {
		$cache = $this->getCache();
		$name = $this->getPrefix().$index;
		$cache->set($name, $data, $this->ttl);

		return $cache->size($name);
	}

	public function isComplete() {
		$prefix = $this->getPrefix();
		$cache = $this->getCache();
		$chunkcount = (int)$this->info['chunkcount'];

		for ($i = ($chunkcount - 1); $i >= 0; $i--) {
			if (!$cache->hasKey($prefix.$i)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Assembles the chunks into the file specified by the path.
	 * Chunks are deleted afterwards.
	 *
	 * @param resource $f target path
	 *
	 * @return integer assembled file size
	 *
	 * @throws \OC\InsufficientStorageException when file could not be fully
	 * assembled due to lack of free space
	 */
	public function assemble($f) {
		$cache = $this->getCache();
		$prefix = $this->getPrefix();
		$count = 0;
		for ($i = 0; $i < $this->info['chunkcount']; $i++) {
			$chunk = $cache->get($prefix.$i);
			// remove after reading to directly save space
			$cache->remove($prefix.$i);
			$count += fwrite($f, $chunk);
			// let php release the memory to work around memory exhausted error with php 5.6
			$chunk = null;
		}

		return $count;
	}

	/**
	 * Returns the size of the chunks already present
	 * @return integer size in bytes
	 */
	public function getCurrentSize() {
		$cache = $this->getCache();
		$prefix = $this->getPrefix();
		$total = 0;
		for ($i = 0; $i < $this->info['chunkcount']; $i++) {
			$total += $cache->size($prefix.$i);
		}
		return $total;
	}

	/**
	 * Removes all chunks which belong to this transmission
	 */
	public function cleanup() {
		$cache = $this->getCache();
		$prefix = $this->getPrefix();
		for ($i = 0; $i < $this->info['chunkcount']; $i++) {
			$cache->remove($prefix.$i);
		}
	}

	/**
	 * Removes one specific chunk
	 * @param string $index
	 */
	public function remove($index) {
		$cache = $this->getCache();
		$prefix = $this->getPrefix();
		$cache->remove($prefix.$index);
	}

	/**
	 * Assembles the chunks into the file specified by the path.
	 * Also triggers the relevant hooks and proxies.
	 *
	 * @param \OC\Files\Storage\Storage $storage storage
	 * @param string $path target path relative to the storage
	 * @return bool true on success or false if file could not be created
	 *
	 * @throws \OC\ServerNotAvailableException
	 */
	public function file_assemble($storage, $path) {
		// use file_put_contents as method because that best matches what this function does
		if (\OC\Files\Filesystem::isValidPath($path)) {
			$target = $storage->fopen($path, 'w');
			if ($target) {
				$count = $this->assemble($target);
				fclose($target);
				return $count > 0;
			} else {
				return false;
			}
		}
		return false;
	}
}
