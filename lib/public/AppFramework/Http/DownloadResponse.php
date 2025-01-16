<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;

/**
 * Prompts the user to download the a file
 * @since 7.0.0
 * @template S of Http::STATUS_*
 * @template C of string
 * @template H of array<string, mixed>
 * @template-extends Response<Http::STATUS_*, array<string, mixed>>
 */
class DownloadResponse extends Response {
	/**
	 * Creates a response that prompts the user to download the file
	 * @param string $filename the name that the downloaded file should have
	 * @param C $contentType the mimetype that the downloaded file should have
	 * @param S $status
	 * @param H $headers
	 * @since 7.0.0
	 */
	public function __construct(string $filename, string $contentType, int $status = Http::STATUS_OK, array $headers = []) {
		parent::__construct($status, $headers);

		$filename = strtr($filename, ['"' => '\\"', '\\' => '\\\\']);

		$this->addHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
		$this->addHeader('Content-Type', $contentType);
	}
}
