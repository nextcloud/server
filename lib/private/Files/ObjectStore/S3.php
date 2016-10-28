<?php
/**
 * @copyright Copyright (c) 2016 Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Files\ObjectStore;

use OCP\Files\ObjectStore\IObjectStore;

// TODO: proper composer
set_include_path(get_include_path() . PATH_SEPARATOR .
	\OC_App::getAppPath('files_external') . '/3rdparty/aws-sdk-php');
require_once 'aws-autoloader.php';

class S3 implements IObjectStore {
	use S3ConnectionTrait;

	public function __construct($parameters) {
		$this->parseParams($parameters);
	}

	/**
	 * @return string the container or bucket name where objects are stored
	 * @since 7.0.0
	 */
	function getStorageId() {
		return $this->id;
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return resource stream with the read data
	 * @throws \Exception when something goes wrong, message will be logged
	 * @since 7.0.0
	 */
	function readObject($urn) {
		// Create the command and serialize the request
		$request = $this->getConnection()->getCommand('GetObject', [
			'Bucket' => $this->bucket,
			'Key' => $urn
		])->prepare();

		$request->dispatch('request.before_send', array(
			'request' => $request
		));

		$headers = $request->getHeaderLines();
		$headers[] = 'Connection: close';

		$opts = [
			'http' => [
				'method' => "GET",
				'header' => $headers
			],
			'ssl' => [
				'verify_peer' => true
			]
		];

		$context = stream_context_create($opts);
		return fopen($request->getUrl(), 'r', false, $context);
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @param resource $stream stream with the data to write
	 * @throws \Exception when something goes wrong, message will be logged
	 * @since 7.0.0
	 */
	function writeObject($urn, $stream) {
		$this->getConnection()->putObject([
			'Bucket' => $this->bucket,
			'Key' => $urn,
			'Body' => $stream
		]);
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return void
	 * @throws \Exception when something goes wrong, message will be logged
	 * @since 7.0.0
	 */
	function deleteObject($urn) {
		$this->getConnection()->deleteObject([
			'Bucket' => $this->bucket,
			'Key' => $urn
		]);
	}

}
