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
use OCP\Console\IOutput;

#[AsCommand(
	name: 'files:object:get',
	description: 'Get the contents of an object',
)]
class Get {
	public function __construct(
		private readonly ObjectUtil $objectUtils,
	) {
	}

	public function __invoke(
		IOutput $output,
		#[Argument(description: 'Object to get')] string $object,
		#[Argument(name: 'output', description: 'Target local file to output to, use - for STDOUT')] string $outputFile,
		#[Option(description: "Bucket to get the object from, only required in cases where it can't be determined from the config", shortcut: 'b')] ?string $bucket = null,
	): ExitCode {
		$objectStore = $this->objectUtils->getObjectStore($bucket, $output);
		if (!$objectStore) {
			return ExitCode::Failure;
		}

		if (!$objectStore->objectExists($object)) {
			$output->writeln("<error>Object $object does not exist</error>");
			return ExitCode::Failure;
		}

		try {
			$source = $objectStore->readObject($object);
		} catch (\Exception $e) {
			$msg = $e->getMessage();
			$output->writeln("<error>Failed to read $object from object store: $msg</error>");
			return ExitCode::Failure;
		}
		$target = $outputFile === '-' ? STDOUT : fopen($outputFile, 'w');
		if (!$target) {
			$output->writeln("<error>Failed to open $outputFile for writing</error>");
			return ExitCode::Failure;
		}

		stream_copy_to_stream($source, $target);
		return ExitCode::Success;
	}
}
