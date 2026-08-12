<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command\Object;

use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\Attribute\Option;
use OCP\Console\ExitCode;
use OCP\Console\IInput;
use OCP\Console\IOutput;
use OCP\Files\IMimeTypeDetector;

#[AsCommand(
	name: 'files:object:put',
	description: 'Write a file to the object store',
)]
class Put {
	public function __construct(
		private readonly ObjectUtil $objectUtils,
		private readonly IMimeTypeDetector $mimeTypeDetector,
	) {
	}

	public function __invoke(
		IOutput $output,
		IInput $consoleInput,
		#[Argument(description: 'Source local path, use - to read from STDIN')]
		string $input,
		#[Argument(description: 'Object to write')]
		string $object,
		#[Option(description: "Bucket where to store the object, only required in cases where it can't be determined from the config", shortcut: 'b')]
		?string $bucket = null,
	): ExitCode|int {
		$objectStore = $this->objectUtils->getObjectStore($bucket, $output);
		if (!$objectStore) {
			return -1;
		}

		if ($fileId = $this->objectUtils->objectExistsInDb($object)) {
			$output->writeln("<error>Warning, object $object belongs to an existing file, overwriting the object contents can lead to unexpected behavior.</error>");
			$output->writeln("You can use <info>occ files:put $input $fileId</info> to write to the file safely.");
			$output->writeln('');

			if (!$consoleInput->confirm('Write to the object anyway? [y/N] ', false)) {
				return -1;
			}
		}

		$source = $input === '-' ? STDIN : fopen($input, 'r');
		if (!$source) {
			$output->writeln("<error>Failed to open $input</error>");
			return ExitCode::Failure;
		}
		$objectStore->writeObject($object, $source, $this->mimeTypeDetector->detectPath($input));
		return ExitCode::Success;
	}
}
