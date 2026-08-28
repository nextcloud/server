<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command\Object;

use OCP\Console\Attribute\AsCommand;
use OCP\Console\Attribute\Option;
use OCP\Console\ExitCode;
use OCP\Console\IInput;
use OCP\Console\IOutput;
use OCP\Console\OutputFormat;
use OCP\Files\ObjectStore\IObjectStoreMetaData;

#[AsCommand(
	name: 'files:object:list',
	description: 'List all objects in the object store',
	supportsOutputFormat: true,
)]
class ListObject {
	private const int CHUNK_SIZE = 100;

	public function __construct(
		private readonly ObjectUtil $objectUtils,
	) {
	}

	public function __invoke(
		IInput $input,
		IOutput $output,
		OutputFormat $outputFormat,
		#[Option(description: "Bucket to list the objects from, only required in cases where it can't be determined from the config", shortcut: 'b')]
		?string $bucket = null,
	): ExitCode {
		$objectStore = $this->objectUtils->getObjectStore($bucket, $output);
		if (!$objectStore) {
			return ExitCode::Failure;
		}

		if (!$objectStore instanceof IObjectStoreMetaData) {
			$output->writeln('<error>Configured object store does currently not support listing objects</error>');
			return ExitCode::Failure;
		}
		$objects = $objectStore->listObjects();
		$objects = $this->objectUtils->formatObjects($objects, $outputFormat === OutputFormat::Plain);
		$output->writeStreamingTableInOutputFormat($objects, self::CHUNK_SIZE);

		return ExitCode::Success;
	}
}
