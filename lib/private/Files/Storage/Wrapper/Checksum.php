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

use OC\Files\Stream\Checksum as ChecksumStream;

/**
 * Class Checksum
 *
 * Computes checksums (default: SHA1) on all files under the /files path.
 * The resulting checksum can be retrieved by call getMetadata($path)
 *
 * @package OC\Files\Storage\Wrapper
 */
class Checksum extends Wrapper {


	/**
	 * @param string $path
	 * @param string $mode
	 * @return false|resource
	 */
	public function fopen($path, $mode) {
		$stream = $this->getWrapperStorage()->fopen($path, $mode);
		if (!self::requiresChecksum($path, $mode)) {
			return $stream;
		}

		return \OC\Files\Stream\Checksum::wrap($stream, $path);
	}


	/**
	 * Checksum is only required for everything under files/
	 * @param $mode
	 * @param $path
	 * @return bool
	 */
	private static function requiresChecksum($path, $mode) {
		return substr($path, 0, 6) === 'files/'
			&&  $mode !== 'r' && $mode !== 'rb';
	}

	/**
	 * @param string $path
	 * @param string $data
	 * @return bool
	 */
	public function file_put_contents($path, $data) {
		$memoryStream = fopen('php://memory', 'r+');
		$checksumStream = \OC\Files\Stream\Checksum::wrap($memoryStream, $path);

		fwrite($checksumStream, $data);
		fclose($checksumStream);

		return $this->getWrapperStorage()->file_put_contents($path, $data);
	}

	/**
	 * @param string $path
	 * @return array
	 */
	public function getMetaData($path) {
		$checksumString = '';

		foreach (ChecksumStream::getChecksums($path) as $algo => $checksum) {
			$checksumString .= sprintf('%s:%s ', strtoupper($algo), $checksum);
		}

		$parentMetaData = $this->getWrapperStorage()->getMetaData($path);
		$parentMetaData['checksum'] = rtrim($checksumString);

		if (!isset($parentMetaData['mimetype'])) {
			$parentMetaData['mimetype'] = 'application/octet-stream';
		}

		return $parentMetaData;
	}
}