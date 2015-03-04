<?php
/**
 * @author Bernhard Posselt
 * @copyright 2015 Bernhard Posselt <dev@bernhard-posselt.com>
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;

/**
 * Class StreamResponse
 *
 * @package OCP\AppFramework\Http
 */
class StreamResponse extends Response implements ICallbackResponse {
	/** @var string */
	private $filePath;

	/**
	 * @param string $filePath the path to the file which should be streamed
	 */
	public function __construct ($filePath) {
		$this->filePath = $filePath;
	}


	/**
	 * Streams the file using readfile
	 *
	 * @param IOutput $output a small wrapper that handles output
	 */
	public function callback (IOutput $output) {
		// handle caching
		if ($output->getHttpResponseCode() !== Http::STATUS_NOT_MODIFIED) {
			if (!file_exists($this->filePath)) {
				$output->setHttpResponseCode(Http::STATUS_NOT_FOUND);
			} elseif ($output->setReadfile($this->filePath) === false) {
				$output->setHttpResponseCode(Http::STATUS_BAD_REQUEST);
			}
		}
	}

}
