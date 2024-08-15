<?php

declare(strict_types = 1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Memcache;

use OCP\IMemcacheTTL;

/**
 * Cache wrapper that logs the cache operation in a log file
 */
class LoggerWrapperCache extends Cache implements IMemcacheTTL {
	/** @var Redis */
	protected $wrappedCache;

	/** @var string $logFile */
	private $logFile;

	/** @var string $prefix */
	protected $prefix;

	public function __construct(Redis $wrappedCache, string $logFile) {
		parent::__construct($wrappedCache->getPrefix());
		$this->wrappedCache = $wrappedCache;
		$this->logFile = $logFile;
	}

	/**
	 * @return string Prefix used for caching purposes
	 */
	public function getPrefix() {
		return $this->prefix;
	}

	protected function getNameSpace() {
		return $this->prefix;
	}

	/** @inheritDoc */
	public function get($key) {
		file_put_contents(
			$this->logFile,
			$this->getNameSpace() . '::get::' . $key . "\n",
			FILE_APPEND
		);
		return $this->wrappedCache->get($key);
	}

	/** @inheritDoc */
	public function set($key, $value, $ttl = 0) {
		file_put_contents(
			$this->logFile,
			$this->getNameSpace() . '::set::' . $key . '::' . $ttl . '::' . json_encode($value) . "\n",
			FILE_APPEND
		);

		return $this->wrappedCache->set($key, $value, $ttl);
	}

	/** @inheritDoc */
	public function hasKey($key) {
		file_put_contents(
			$this->logFile,
			$this->getNameSpace() . '::hasKey::' . $key . "\n",
			FILE_APPEND
		);

		return $this->wrappedCache->hasKey($key);
	}

	/** @inheritDoc */
	public function remove($key) {
		file_put_contents(
			$this->logFile,
			$this->getNameSpace() . '::remove::' . $key . "\n",
			FILE_APPEND
		);

		return $this->wrappedCache->remove($key);
	}

	/** @inheritDoc */
	public function clear($prefix = '') {
		file_put_contents(
			$this->logFile,
			$this->getNameSpace() . '::clear::' . $prefix . "\n",
			FILE_APPEND
		);

		return $this->wrappedCache->clear($prefix);
	}

	/** @inheritDoc */
	public function add($key, $value, $ttl = 0) {
		file_put_contents(
			$this->logFile,
			$this->getNameSpace() . '::add::' . $key . '::' . $value . "\n",
			FILE_APPEND
		);

		return $this->wrappedCache->add($key, $value, $ttl);
	}

	/** @inheritDoc */
	public function inc($key, $step = 1) {
		file_put_contents(
			$this->logFile,
			$this->getNameSpace() . '::inc::' . $key . "\n",
			FILE_APPEND
		);

		return $this->wrappedCache->inc($key, $step);
	}

	/** @inheritDoc */
	public function dec($key, $step = 1) {
		file_put_contents(
			$this->logFile,
			$this->getNameSpace() . '::dec::' . $key . "\n",
			FILE_APPEND
		);

		return $this->wrappedCache->dec($key, $step);
	}

	/** @inheritDoc */
	public function cas($key, $old, $new) {
		file_put_contents(
			$this->logFile,
			$this->getNameSpace() . '::cas::' . $key . "\n",
			FILE_APPEND
		);

		return $this->wrappedCache->cas($key, $old, $new);
	}

	/** @inheritDoc */
	public function cad($key, $old) {
		file_put_contents(
			$this->logFile,
			$this->getNameSpace() . '::cad::' . $key . "\n",
			FILE_APPEND
		);

		return $this->wrappedCache->cad($key, $old);
	}

	/** @inheritDoc */
	public function ncad(string $key, mixed $old): bool {
		file_put_contents(
			$this->logFile,
			$this->getNameSpace() . '::ncad::' . $key . "\n",
			FILE_APPEND
		);

		return $this->wrappedCache->cad($key, $old);
	}

	/** @inheritDoc */
	public function setTTL(string $key, int $ttl) {
		$this->wrappedCache->setTTL($key, $ttl);
	}

	public function getTTL(string $key): int|false {
		return $this->wrappedCache->getTTL($key);
	}

	public function compareSetTTL(string $key, mixed $value, int $ttl): bool {
		return $this->wrappedCache->compareSetTTL($key, $value, $ttl);
	}

	public static function isAvailable(): bool {
		return true;
	}
}
