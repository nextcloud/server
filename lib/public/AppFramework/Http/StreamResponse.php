<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;

/**
 * Class StreamResponse
 *
 * @since 8.1.0
 * @template S of Http::STATUS_*
 * @template H of array<string, mixed>
 * @template-extends Response<Http::STATUS_*, array<string, mixed>>
 */
class StreamResponse extends Response implements ICallbackResponse {
	/** @var string */
	private $filePath;

	/**
	 * @param string|resource $filePath the path to the file or a file handle which should be streamed
	 * @param S $status
	 * @param H $headers
	 * @since 8.1.0
	 */
	public function __construct(mixed $filePath, int $status = Http::STATUS_OK, array $headers = []) {
		parent::__construct($status, $headers);

		$this->filePath = $filePath;
	}


	/**
	 * Streams the file using readfile
	 *
	 * @param IOutput $output a small wrapper that handles output
	 * @since 8.1.0
	 */
	public function callback(IOutput $output) {
		// handle caching
		if ($output->getHttpResponseCode() !== Http::STATUS_NOT_MODIFIED) {
			if (!(is_resource($this->filePath) || file_exists($this->filePath))) {
				$output->setHttpResponseCode(Http::STATUS_NOT_FOUND);
			} elseif ($output->setReadfile($this->filePath) === false) {
				$output->setHttpResponseCode(Http::STATUS_BAD_REQUEST);
			}
		}
	}
}
