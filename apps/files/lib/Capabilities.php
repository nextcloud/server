<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files;

use OC\Files\FilenameValidator;
use OCA\Files\Service\ChunkedUploadConfig;
use OCP\Capabilities\ICapability;

class Capabilities implements ICapability {

	public function __construct(
		protected FilenameValidator $filenameValidator,
	) {
	}

	/**
	 * Return this classes capabilities
	 *
	 * @return array{files: array{'$comment': ?string, bigfilechunking: bool, blacklisted_files: array<mixed>, forbidden_filenames: list<string>, forbidden_filename_basenames: list<string>, forbidden_filename_characters: list<string>, forbidden_filename_extensions: list<string>, chunked_upload: array{max_size: int, max_parallel_count: int}}}
	 */
	public function getCapabilities(): array {
		return [
			'files' => [
				'$comment' => '"blacklisted_files" is deprecated as of Nextcloud 30, use "forbidden_filenames" instead',
				'blacklisted_files' => $this->filenameValidator->getForbiddenFilenames(),
				'forbidden_filenames' => $this->filenameValidator->getForbiddenFilenames(),
				'forbidden_filename_basenames' => $this->filenameValidator->getForbiddenBasenames(),
				'forbidden_filename_characters' => $this->filenameValidator->getForbiddenCharacters(),
				'forbidden_filename_extensions' => $this->filenameValidator->getForbiddenExtensions(),

				'bigfilechunking' => true,
				'chunked_upload' => [
					'max_size' => ChunkedUploadConfig::getMaxChunkSize(),
					'max_parallel_count' => ChunkedUploadConfig::getMaxParallelCount(),
				],
			],
		];
	}
}
