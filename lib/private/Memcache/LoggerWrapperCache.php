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

		return $this->wrappedCache->set($key, $value, $$ttl);
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
	public function setTTL($key, $ttl) {
		$this->wrappedCache->setTTL($key, $ttl);
	}

	public static function isAvailable(): bool {
		return true;
	}
}
