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

use Guzzle\Http\EntityBody;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Service\Command\CommandInterface;
use Guzzle\Stream\PhpStreamRequestFactory;
use Icewind\Streams\CallbackWrapper;
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
	 * Serialize and sign a command, returning a request object
	 *
	 * @param CommandInterface $command Command to sign
	 *
	 * @return RequestInterface
	 */
	protected function getSignedRequest($command) {
		$request = $command->prepare();
		$request->dispatch('request.before_send', array('request' => $request));

		return $request;
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return resource stream with the read data
	 * @throws \Exception when something goes wrong, message will be logged
	 * @since 7.0.0
	 */
	function readObject($urn) {
		// Create the command and serialize the request
		$request = $this->getSignedRequest($this->getConnection()->getCommand('GetObject', [
			'Bucket' => $this->bucket,
			'Key' => $urn
		]));
		// Create a stream that uses the EntityBody object
		$factory = new PhpStreamRequestFactory();
		/** @var EntityBody $body */
		$body = $factory->fromRequest($request, array(), array('stream_class' => 'Guzzle\Http\EntityBody'));
		$stream = $body->getStream();

		// we need to keep the guzzle request in scope untill the stream is closed
		return CallbackWrapper::wrap($stream, null, null, function () use ($body) {
			$body->close();
		});
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
