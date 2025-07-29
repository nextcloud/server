<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;
use OCP\Files\File;
use OCP\Files\SimpleFS\ISimpleFile;

/**
 * Class FileDisplayResponse
 *
 * @since 11.0.0
 * @template S of Http::STATUS_*
 * @template H of array<string, mixed>
 * @template-extends Response<Http::STATUS_*, array<string, mixed>>
 */
class FileDisplayResponse extends Response implements ICallbackResponse {
	/** @var File|ISimpleFile */
	private $file;

	/**
	 * FileDisplayResponse constructor.
	 *
	 * @param File|ISimpleFile $file
	 * @param S $statusCode
	 * @param H $headers
	 * @since 11.0.0
	 */
	public function __construct(File|ISimpleFile $file, int $statusCode = Http::STATUS_OK, array $headers = []) {
		parent::__construct($statusCode, $headers);

		$this->file = $file;
		$this->addHeader('Content-Disposition', 'inline; filename="' . rawurldecode($file->getName()) . '"');

		$this->setETag($file->getEtag());
		$lastModified = new \DateTime();
		$lastModified->setTimestamp($file->getMTime());
		$this->setLastModified($lastModified);
	}

	/**
	 * @param IOutput $output
	 * @since 11.0.0
	 */
	public function callback(IOutput $output) {
		if ($output->getHttpResponseCode() !== Http::STATUS_NOT_MODIFIED) {
			$output->setHeader('Content-Length: ' . $this->file->getSize());
			$output->setOutput($this->file->getContent());
		}
	}
}
