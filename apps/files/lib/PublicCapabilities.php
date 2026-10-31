<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files;

use OC\Files\FilenameValidator;
use OCP\Capabilities\IPublicCapability;

class PublicCapabilities implements IPublicCapability {

	public function __construct(
		protected FilenameValidator $filenameValidator,
	) {
	}

	/**
	 * Return this classes capabilities
	 *
	 * @return array{files: array{'$comment': ?string, blacklisted_files: list<mixed>, forbidden_filenames: list<string>, forbidden_filename_basenames: list<string>, forbidden_filename_characters: list<string>, forbidden_filename_extensions: list<string>}}
	 */
	#[\Override]
	public function getCapabilities(): array {
		return [
			'files' => [
				'$comment' => '"blacklisted_files" is deprecated as of Nextcloud 30, use "forbidden_filenames" instead',
				'blacklisted_files' => $this->filenameValidator->getForbiddenFilenames(),
				'forbidden_filenames' => $this->filenameValidator->getForbiddenFilenames(),
				'forbidden_filename_basenames' => $this->filenameValidator->getForbiddenBasenames(),
				'forbidden_filename_characters' => $this->filenameValidator->getForbiddenCharacters(),
				'forbidden_filename_extensions' => $this->filenameValidator->getForbiddenExtensions(),
			],
		];
	}
}
