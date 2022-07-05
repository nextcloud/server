<?php

namespace OC\DB\ORM;

use OCP\ICache;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class CacheItemAdapter implements CacheItemInterface {
	private ICache $cacheAdapter;
	private string $key;
	private $value;
	private bool $fetched = false;
	private ?\DateTime $_expireAt = null;
	private int $_expireAfter = -1;

	public function __construct(ICache $cache, $key) {
		$this->cache = $cache;
		$this->key = $key;
	}

	private function fetch(): void {
		if (!$this->fetched) {
			$this->value = $this->cache->get($this->key);
			$this->fetched = true;
		}
	}

	public function getKey() {
		return $this->key;
	}

	public function get() {
		$this->fetch();
		return $this->value;
	}

	public function isHit() {
		$this->fetch();
		return $this->value !== null;
	}

	public function set($value) {
		$this->value = $value;
	}

	public function expiresAt($expiration) {
		$this->_expireAt = $expiration;
	}

	public function expiresAfter($time) {
		$this->_expireAfter = $time;
	}

	public function getExpireAt(): ?\DateTime
	{
		return $this->_expireAt;
	}

	public function getExpireAfter(): int
	{
		return $this->_expireAfter;
	}
}

class Psr6CacheAdapter implements CacheItemPoolInterface {
	private ICache $cache;

	public function __construct(ICache $cache) {
		$this->cache = $cache;
	}

	public function getItem($key) {
		return new CacheItemAdapter($this->cache, $key);
	}

	public function getItems(array $keys = array()) {
		for (int )
		// TODO: Implement getItems() method.
	}

	public function hasItem($key)
	{
		// TODO: Implement hasItem() method.
	}

	public function clear()
	{
		// TODO: Implement clear() method.
	}

	public function deleteItem($key)
	{
		// TODO: Implement deleteItem() method.
	}

	public function deleteItems(array $keys)
	{
		// TODO: Implement deleteItems() method.
	}

	public function save(CacheItemInterface $item)
	{
		// TODO: Implement save() method.
	}

	public function saveDeferred(CacheItemInterface $item)
	{
		// TODO: Implement saveDeferred() method.
	}

	public function commit()
	{
		// TODO: Implement commit() method.
	}
}
