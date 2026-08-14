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
use OCP\Console\OutputFormat;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\ObjectStore\IObjectStoreMetaData;
use OCP\Util;

#[AsCommand(
	name: 'files:object:info',
	description: 'Get the metadata of an object',
	supportsOutputFormat: true,
)]
class Info {
	public function __construct(
		private readonly ObjectUtil $objectUtils,
		private readonly IMimeTypeDetector $mimeTypeDetector,
	) {
	}

	public function __invoke(
		IInput $input,
		IOutput $output,
		OutputFormat $outputFormat,
		#[Argument(description: 'Object to get')]
		string $object,
		#[Option(description: "Bucket to get the object from, only required in cases where it can't be determined from the config", shortcut: 'b')]
		?string $bucket = null,
	): ExitCode {
		$objectStore = $this->objectUtils->getObjectStore($bucket, $output);
		if (!$objectStore) {
			return ExitCode::Failure;
		}

		if (!$objectStore instanceof IObjectStoreMetaData) {
			$output->writeln('<error>Configured object store does currently not support retrieve metadata</error>');
			return ExitCode::Failure;
		}

		if (!$objectStore->objectExists($object)) {
			$output->writeln("<error>Object $object does not exist</error>");
			return ExitCode::Failure;
		}

		try {
			$meta = $objectStore->getObjectMetaData($object);
		} catch (\Exception $e) {
			$msg = $e->getMessage();
			$output->writeln("<error>Failed to read $object from object store: $msg</error>");
			return ExitCode::Failure;
		}

		if ($outputFormat === OutputFormat::Plain && isset($meta['size'])) {
			$meta['size'] = Util::humanFileSize($meta['size']);
		}
		if (isset($meta['mtime'])) {
			$meta['mtime'] = $meta['mtime']->format(\DateTimeImmutable::ATOM);
		}
		if (!isset($meta['mimetype'])) {
			$handle = $objectStore->readObject($object);
			$head = fread($handle, 8192);
			fclose($handle);
			$meta['mimetype'] = $this->mimeTypeDetector->detectString($head);
		}

		$output->writeArrayInOutputFormat($meta);

		return ExitCode::Success;
	}
}
