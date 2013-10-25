<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage\Wrapper;

class Quota extends Wrapper {

	/**
	 * @var int $quota
	 */
	protected $quota;

	/**
	 * @param array $parameters
	 */
	public function __construct($parameters) {
		$this->storage = $parameters['storage'];
		$this->quota = $parameters['quota'];
	}

	protected function getSize($path) {
		$cache = $this->getCache();
		$data = $cache->get($path);
		if (is_array($data) and isset($data['size'])) {
			return $data['size'];
		} else {
			return \OC\Files\SPACE_NOT_COMPUTED;
		}
	}

	/**
	 * Get free space as limited by the quota
	 *
	 * @param string $path
	 * @return int
	 */
	public function free_space($path) {
		if ($this->quota < 0) {
			return $this->storage->free_space($path);
		} else {
			$used = $this->getSize('');
			if ($used < 0) {
				return \OC\Files\SPACE_NOT_COMPUTED;
			} else {
				$free = $this->storage->free_space($path);
				return min($free, (max($this->quota - $used, 0)));
			}
		}
	}

	/**
	 * see http://php.net/manual/en/function.file_put_contents.php
	 *
	 * @param string $path
	 * @param string $data
	 * @return bool
	 */
	public function file_put_contents($path, $data) {
		$free = $this->free_space('');
		if ($free < 0 or strlen($data) < $free) {
			return $this->storage->file_put_contents($path, $data);
		} else {
			return false;
		}
	}

	/**
	 * see http://php.net/manual/en/function.copy.php
	 *
	 * @param string $source
	 * @param string $target
	 * @return bool
	 */
	public function copy($source, $target) {
		$free = $this->free_space('');
		if ($free < 0 or $this->getSize($source) < $free) {
			return $this->storage->copy($source, $target);
		} else {
			return false;
		}
	}

	/**
	 * see http://php.net/manual/en/function.fopen.php
	 *
	 * @param string $path
	 * @param string $mode
	 * @return resource
	 */
	public function fopen($path, $mode) {
		$source = $this->storage->fopen($path, $mode);
		$free = $this->free_space('');
		if ($free >= 0 && $mode !== 'r') {
			return \OC\Files\Stream\Quota::wrap($source, $free);
		} else {
			return $source;
		}
	}
}
