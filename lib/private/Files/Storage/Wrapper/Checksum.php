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

namespace OC\Files\Storage\Wrapper;

use Icewind\Streams\CallbackWrapper;
use OC\Cache\CappedMemoryCache;
use OC\Files\Stream\Checksum as ChecksumStream;
use OCP\Files\IHomeStorage;
use OCP\ILogger;

/**
 * Class Checksum
 *
 * Computes checksums (default: SHA1, MD5, ADLER32) on all files under the /files path.
 * The resulting checksum can be retrieved by call getMetadata($path)
 *
 * If a file is read and has no checksum oc_filecache gets updated accordingly.
 *
 *
 * @package OC\Files\Storage\Wrapper
 */
class Checksum extends Wrapper {


	const NOT_REQUIRED = 0;
	/** Calculate checksum on write (to be stored in oc_filecache) */
	const PATH_NEW_OR_UPDATED = 1;
	/** File needs to be checksummed on first read because it is already in cache but has no checksum */
	const PATH_IN_CACHE_WITHOUT_CHECKSUM = 2;

	/**
	 * @param string $path
	 * @param string $mode
	 * @return false|resource
	 */
	public function fopen($path, $mode) {
		$stream = $this->getWrapperStorage()->fopen($path, $mode);
		if (!is_resource($stream)) {
			// don't wrap on error
			return $stream;
		}

		$requirement = $this->getChecksumRequirement($path, $mode);

		if ($requirement === self::PATH_NEW_OR_UPDATED ||
			$requirement === self::PATH_IN_CACHE_WITHOUT_CHECKSUM
		) {
			return $this->wrapChecksumStream($stream, $path);
		}

		return $stream;
	}

	private function wrapChecksumStream($stream, $path) {
		return \OC\Files\Stream\Checksum::wrap($stream, function (array $hashes) use ($path) {
			$cache = $this->getCache();
			$cache->put($path, [
				'checksum' => self::getChecksumsInDbFormat($hashes)
			]);
		});
	}

	/**
	 * @param $mode
	 * @param $path
	 * @return int
	 */
	private function getChecksumRequirement($path, $mode) {
		$isNormalFile = (!$this->getWrapperStorage() instanceof IHomeStorage) || strpos($path, 'files/') === 0;
		$fileIsWritten = $mode !== 'r' && $mode !== 'rb';

		if ($isNormalFile && $fileIsWritten) {
			return self::PATH_NEW_OR_UPDATED;
		}

		// file could be in cache but without checksum for example
		// if mounted from ext. storage
		$cache = $this->getCache($path);
		$cacheEntry = $cache->get($path);

		if ($cacheEntry && empty($cacheEntry['checksum'])) {
			return self::PATH_IN_CACHE_WITHOUT_CHECKSUM;
		}

		return self::NOT_REQUIRED;
	}

	/**
	 * @param array $hashes
	 * @return string
	 */
	private static function getChecksumsInDbFormat(array $hashes) {
		$checksumString = '';
		foreach ($hashes as $algo => $checksum) {
			$checksumString .= sprintf('%s:%s ', strtoupper($algo), $checksum);
		}

		return rtrim($checksumString);
	}

	/**
	 * @param string $path
	 * @param string $data
	 * @return bool
	 */
	public function file_put_contents($path, $data) {
		$fh = $this->fopen($path, 'w');
		if (!$fh) {
			return false;
		}
		fwrite($fh, $data);
		fclose($fh);

		return true;
	}
}
