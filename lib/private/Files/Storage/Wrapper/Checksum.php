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
	 * @param array $parameters
	 */
	public function __construct($parameters) {
		if (isset($parameters['algo'])) {
			ChecksumStream::setAlgo($parameters['algo']);
		}
		parent::__construct($parameters);
	}

	/**
	 * @param string $path
	 * @param string $mode
	 * @return false|resource
	 */
	public function fopen($path, $mode) {
		$stream = $this->getWrapperStorage()->fopen($path, $mode);
		if (!self::requiresChecksum($path)) {
			return $stream;
		}

		return \OC\Files\Stream\Checksum::wrap($stream, $path);
	}


	/**
	 * Checksum is only required for everything under files/
	 * @param $path
	 * @return bool
	 */
	private static function requiresChecksum($path) {
		return substr($path, 0, 6) === 'files/';
	}

	/**
	 * @param string $path
	 * @param string $data
	 * @return bool
	 */
	public function file_put_contents($path, $data) {
		$stream = fopen('occhecksum://','r+');
		fwrite($stream, $data);
		fclose($stream);

		return parent::file_put_contents($path, $data);
	}

	/**
	 * @param string $path
	 * @return array
	 */
	public function getMetaData($path) {
		$parentMetaData = parent::getMetaData($path);
		$parentMetaData['checksum'] = ChecksumStream::getChecksum($path);

		return $parentMetaData;
	}
}