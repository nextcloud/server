<?php

/**
 * @author Ilja Neumann <ineumann@owncloud.com>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Files\Stream;


use Icewind\Streams\Wrapper;
use OC\Cache\CappedMemoryCache;

/**
 * Computes the checksum of the wrapped stream. The checksum can be retrieved with
 * getChecksum after the stream is closed.
 *
 *
 * @package OC\Files\Stream
 */
class Checksum extends Wrapper {

	private static $algo = 'sha1';

	/** @var  resource */
	private $hashCtx;

	/** @var CappedMemoryCache Key is path, value is the checksum */
	private static $checksums;


	public function __construct() {
		$this->hashCtx = hash_init(self::$algo);

		if (!self::$checksums) {
			self::$checksums = new CappedMemoryCache();
		}
	}


	/**
	 * @param $source
	 * @param $path
	 * @return resource
	 */
	public static function wrap($source, $path) {
		$context = stream_context_create([
			'occhecksum' => [
				'source' => $source,
				'path' => $path
			]
		]);

		return Wrapper::wrapSource(
			$source, $context, 'occhecksum', self::class
		);
	}


	/**
	 * @param string $path
	 * @param array $options
	 * @return bool
	 */
	public function dir_opendir($path, $options) {
		return true;
	}

	/**
	 * @param string $path
	 * @param string $mode
	 * @param int $options
	 * @param string $opened_path
	 * @return bool
	 */
	public function stream_open($path, $mode, $options, &$opened_path) {
		$context = parent::loadContext('occhecksum');
		$this->setSourceStream($context['source']);

		return true;
	}

	/**
	 * @param int $count
	 * @return string
	 */
	public function stream_read($count) {
		$data = parent::stream_read($count);
		hash_update($this->hashCtx, $data);

		return $data;
	}

	/**
	 * @param string $data
	 * @return int
	 */
	public function stream_write($data) {
		hash_update($this->hashCtx, $data);

		return parent::stream_write($data);
	}

	/**
	 * @return bool
	 */
	public function stream_close() {
		self::$checksums[$this->getPathFromContext()] = sprintf(
        	'%s:%s', strtoupper(self::$algo), hash_final($this->hashCtx)
		);

		return parent::stream_close();
	}

	public function dir_closedir() {
		if (!isset($this->source)) {
			return false;
		}
		return parent::dir_closedir();
	}

	/**
	 * @return mixed
	 * @return string
	 */
	private function getPathFromContext() {
		$ctx = stream_context_get_options($this->context);

		return $ctx['occhecksum']['path'];
	}

	/**
	 * @param $path
	 * @return string
	 */
	public static function getChecksum($path) {
		if (!isset(self::$checksums[$path])) {
			return null;
		}

		return self::$checksums[$path];
	}

	/**
	 * @return CappedMemoryCache
	 */
	public static function getChecksums() {
		if (!self::$checksums) {
			self::$checksums = new CappedMemoryCache();
		}

		return self::$checksums;
	}

	/**
	 * @return string
	 */
	public static function getAlgo() {
		return self::$algo;
	}

	/**
	 * @param string $algo
	 */
	public static function setAlgo($algo) {
		self::$algo = $algo;
	}
}
