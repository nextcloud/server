<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;

/**
 * Class DataDownloadResponse
 *
 * @since 8.0.0
 * @template S of Http::STATUS_*
 * @template C of string
 * @template H of array<string, mixed>
 * @template-extends DownloadResponse<Http::STATUS_*, string, array<string, mixed>>
 */
class DataDownloadResponse extends DownloadResponse {
	/**
	 * @var string
	 */
	private $data;

	/**
	 * Creates a response that prompts the user to download the text
	 * @param string $data text to be downloaded
	 * @param string $filename the name that the downloaded file should have
	 * @param C $contentType the mimetype that the downloaded file should have
	 * @param S $status
	 * @param H $headers
	 * @since 8.0.0
	 */
	public function __construct(string $data, string $filename, string $contentType, int $status = Http::STATUS_OK, array $headers = []) {
		$this->data = $data;
		parent::__construct($filename, $contentType, $status, $headers);
	}

	/**
	 * @param string $data
	 * @since 8.0.0
	 */
	public function setData($data) {
		$this->data = $data;
	}

	/**
	 * @return string
	 * @since 8.0.0
	 */
	public function render() {
		return $this->data;
	}
}
