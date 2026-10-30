<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command;

use OCP\Console\Attribute\AsCommand;
use OCP\Console\Attribute\Option;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;
use OCP\Console\Verbosity;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

#[AsCommand(
	name: 'files:repair-tree',
	description: 'Try and repair malformed filesystem tree structures (may be necessary to run multiple times for nested malformations)',
)]
class RepairTree {
	public const int CHUNK_SIZE = 200;

	public function __construct(
		private readonly IDBConnection $connection,
	) {
	}

	public function __invoke(
		IOutput $output,
		#[Option]
		bool $dryRun = false,
		#[Option(name: 'storage-id', description: 'If set, only repair files within the given storage numeric ID', shortcut: 's')]
		?string $storageId = null,
		#[Option(description: 'If set, only repair files within the given path', shortcut: 'p')]
		?string $path = null,
	): ExitCode {
		$rows = $this->findBrokenTreeBits($storageId, $path);
		$fix = !$dryRun;

		$output->writeln('Found ' . count($rows) . ' file entries with an invalid path');

		if ($fix) {
			$this->connection->beginTransaction();
		}

		$query = $this->connection->getQueryBuilder();
		$query->update('filecache')
			->set('path', $query->createParameter('path'))
			->set('path_hash', $query->func()->md5($query->createParameter('path')))
			->set('storage', $query->createParameter('storage'))
			->where($query->expr()->eq('fileid', $query->createParameter('fileid')));

		foreach ($rows as $row) {
			$output->writeln("Path of file {$row['fileid']} is {$row['path']} but should be {$row['parent_path']}/{$row['name']} based on its parent", Verbosity::Verbose);

			if ($fix) {
				$fileId = $this->getFileId((int)$row['parent_storage'], $row['parent_path'] . '/' . $row['name']);
				if ($fileId > 0) {
					$output->writeln("Cache entry has already be recreated with id $fileId, deleting instead");
					$this->deleteById((int)$row['fileid']);
				} else {
					$query->setParameters([
						'fileid' => $row['fileid'],
						'path' => $row['parent_path'] . '/' . $row['name'],
						'storage' => $row['parent_storage'],
					]);
					$query->executeStatement();
				}
			}
		}

		if ($fix) {
			$this->connection->commit();
		}

		return ExitCode::Success;
	}

	private function getFileId(int $storage, string $path) {
		$query = $this->connection->getQueryBuilder();
		$query->select('fileid')
			->from('filecache')
			->where($query->expr()->eq('storage', $query->createNamedParameter($storage)))
			->andWhere($query->expr()->eq('path_hash', $query->createNamedParameter(md5($path))));
		return $query->executeQuery()->fetchOne();
	}

	private function deleteById(int $fileId): void {
		$query = $this->connection->getQueryBuilder();
		$query->delete('filecache')
			->where($query->expr()->eq('fileid', $query->createNamedParameter($fileId)));
		$query->executeStatement();
	}

	private function findBrokenTreeBits(?string $storageId, ?string $path): array {
		$query = $this->connection->getQueryBuilder();

		$query->select('f.fileid', 'f.path', 'f.parent', 'f.name')
			->selectAlias('p.path', 'parent_path')
			->selectAlias('p.storage', 'parent_storage')
			->from('filecache', 'f')
			->innerJoin('f', 'filecache', 'p', $query->expr()->eq('f.parent', 'p.fileid'))
			->where($query->expr()->orX(
				$query->expr()->andX(
					$query->expr()->neq('p.path_hash', $query->createNamedParameter(md5(''))),
					$query->expr()->neq('f.path', $query->func()->concat('p.path', $query->func()->concat($query->createNamedParameter('/'), 'f.name')))
				),
				$query->expr()->andX(
					$query->expr()->eq('p.path_hash', $query->createNamedParameter(md5(''))),
					$query->expr()->neq('f.path', 'f.name')
				),
				$query->expr()->neq('f.storage', 'p.storage')
			));

		if ($storageId !== null) {
			$query->andWhere($query->expr()->eq('f.storage', $query->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)));
		}

		if ($path !== null) {
			$query->andWhere($query->expr()->like('f.path', $query->createNamedParameter($path . '%')));
		}

		return $query->executeQuery()->fetchAllAssociative();
	}
}
