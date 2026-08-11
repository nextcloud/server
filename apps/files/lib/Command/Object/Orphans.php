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
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\ObjectStore\IObjectStoreMetaData;
use OCP\IDBConnection;

#[AsCommand(
	name: 'files:object:orphans',
	description: 'List all objects in the object store that don\'t have a matching entry in the database',
	supportsOutputFormat: true,
)]
class Orphans {
	private const CHUNK_SIZE = 100;

	private ?IQueryBuilder $query = null;

	public function __construct(
		private readonly ObjectUtil $objectUtils,
		private readonly IDBConnection $connection,
	) {
	}

	private function getQuery(): IQueryBuilder {
		if (!$this->query) {
			$this->query = $this->connection->getQueryBuilder();
			$this->query->select('fileid')
				->from('filecache')
				->where($this->query->expr()->eq('fileid', $this->query->createParameter('file_id')));
		}
		return $this->query;
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
		$prefixLength = strlen('urn:oid:');

		$objects = $objectStore->listObjects('urn:oid:');
		$orphans = new \CallbackFilterIterator($objects, function (array $object) use ($prefixLength) {
			$fileId = (int)substr($object['urn'], $prefixLength);
			return !$this->fileIdInDb($fileId);
		});

		$orphans = $this->objectUtils->formatObjects($orphans, $outputFormat === OutputFormat::Plain);
		$output->writeStreamingTableInOutputFormat($orphans, self::CHUNK_SIZE);

		return ExitCode::Success;
	}

	private function fileIdInDb(int $fileId): bool {
		$query = $this->getQuery();
		$query->setParameter('file_id', $fileId, IQueryBuilder::PARAM_INT);
		$result = $query->executeQuery();
		return $result->fetchOne() !== false;
	}
}
