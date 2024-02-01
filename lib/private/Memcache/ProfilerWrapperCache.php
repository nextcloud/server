<?php

declare(strict_types = 1);
/**
 * @copyright 2022 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Memcache;

use OC\AppFramework\Http\Request;
use OCP\AppFramework\Http\Response;
use OCP\DataCollector\AbstractDataCollector;
use OCP\IMemcacheTTL;

/**
 * Cache wrapper that logs profiling information
 */
class ProfilerWrapperCache extends AbstractDataCollector implements IMemcacheTTL, \ArrayAccess {
	/** @var Redis  $wrappedCache*/
	protected $wrappedCache;

	/** @var string $prefix */
	protected $prefix;

	/** @var string $type */
	private $type;

	public function __construct(Redis $wrappedCache, string $type) {
		$this->prefix = $wrappedCache->getPrefix();
		$this->wrappedCache = $wrappedCache;
		$this->type = $type;
		$this->data['queries'] = [];
		$this->data['cacheHit'] = 0;
		$this->data['cacheMiss'] = 0;
	}

	public function getPrefix(): string {
		return $this->prefix;
	}

	/** @inheritDoc */
	public function get($key) {
		$start = microtime(true);
		$ret = $this->wrappedCache->get($key);
		if ($ret === null) {
			$this->data['cacheMiss']++;
		} else {
			$this->data['cacheHit']++;
		}
		$this->data['queries'][] = [
			'start' => $start,
			'end' => microtime(true),
			'op' => $this->getPrefix() . '::get::' . $key,
			'hit' => $ret !== null,
		];
		return $ret;
	}

	/** @inheritDoc */
	public function set($key, $value, $ttl = 0) {
		$start = microtime(true);
		$ret = $this->wrappedCache->set($key, $value, $ttl);
		$this->data['queries'][] = [
			'start' => $start,
			'end' => microtime(true),
			'op' => $this->getPrefix() . '::set::' . $key,
		];
		return $ret;
	}

	/** @inheritDoc */
	public function hasKey($key) {
		$start = microtime(true);
		$ret = $this->wrappedCache->hasKey($key);
		$this->data['queries'][] = [
			'start' => $start,
			'end' => microtime(true),
			'op' => $this->getPrefix() . '::hasKey::' . $key,
		];
		return $ret;
	}

	/** @inheritDoc */
	public function remove($key) {
		$start = microtime(true);
		$ret = $this->wrappedCache->remove($key);
		$this->data['queries'][] = [
			'start' => $start,
			'end' => microtime(true),
			'op' => $this->getPrefix() . '::remove::' . $key,
		];
		return $ret;
	}

	/** @inheritDoc */
	public function clear($prefix = '') {
		$start = microtime(true);
		$ret = $this->wrappedCache->clear($prefix);
		$this->data['queries'][] = [
			'start' => $start,
			'end' => microtime(true),
			'op' => $this->getPrefix() . '::clear::' . $prefix,
		];
		return $ret;
	}

	/** @inheritDoc */
	public function add($key, $value, $ttl = 0) {
		$start = microtime(true);
		$ret = $this->wrappedCache->add($key, $value, $ttl);
		$this->data['queries'][] = [
			'start' => $start,
			'end' => microtime(true),
			'op' => $this->getPrefix() . '::add::' . $key,
		];
		return $ret;
	}

	/** @inheritDoc */
	public function inc($key, $step = 1) {
		$start = microtime(true);
		$ret = $this->wrappedCache->inc($key, $step);
		$this->data['queries'][] = [
			'start' => $start,
			'end' => microtime(true),
			'op' => $this->getPrefix() . '::inc::' . $key,
		];
		return $ret;
	}

	/** @inheritDoc */
	public function dec($key, $step = 1) {
		$start = microtime(true);
		$ret = $this->wrappedCache->dec($key, $step);
		$this->data['queries'][] = [
			'start' => $start,
			'end' => microtime(true),
			'op' => $this->getPrefix() . '::dev::' . $key,
		];
		return $ret;
	}

	/** @inheritDoc */
	public function cas($key, $old, $new) {
		$start = microtime(true);
		$ret = $this->wrappedCache->cas($key, $old, $new);
		$this->data['queries'][] = [
			'start' => $start,
			'end' => microtime(true),
			'op' => $this->getPrefix() . '::cas::' . $key,
		];
		return $ret;
	}

	/** @inheritDoc */
	public function cad($key, $old) {
		$start = microtime(true);
		$ret = $this->wrappedCache->cad($key, $old);
		$this->data['queries'][] = [
			'start' => $start,
			'end' => microtime(true),
			'op' => $this->getPrefix() . '::cad::' . $key,
		];
		return $ret;
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

	public function offsetExists($offset): bool {
		return $this->hasKey($offset);
	}

	public function offsetSet($offset, $value): void {
		$this->set($offset, $value);
	}

	/**
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset) {
		return $this->get($offset);
	}

	public function offsetUnset($offset): void {
		$this->remove($offset);
	}

	public function collect(Request $request, Response $response, \Throwable $exception = null): void {
		// Nothing to do here $data is already ready
	}

	public function getName(): string {
		return 'cache/' . $this->type . '/' . $this->prefix;
	}

	public static function isAvailable(): bool {
		return true;
	}
}
