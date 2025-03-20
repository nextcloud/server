<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command\Object;

use OC\Core\Command\Base;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\ObjectStore\IObjectStoreMetaData;
use OCP\IDBConnection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Orphans extends Base {
	private const CHUNK_SIZE = 100;

	private IQueryBuilder $query;

	public function __construct(
		private ObjectUtil $objectUtils,
		IDBConnection $connection,
	) {
		parent::__construct();

		$this->query = $connection->getQueryBuilder();
		$this->query->select('fileid')
			->from('filecache')
			->where($this->query->expr()->eq('fileid', $this->query->createParameter('file_id')));
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('files:object:orphans')
			->setDescription('List all objects in the object store that don\'t have a matching entry in the database')
			->addOption('bucket', 'b', InputOption::VALUE_REQUIRED, "Bucket to list the objects from, only required in cases where it can't be determined from the config");
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$objectStore = $this->objectUtils->getObjectStore($input->getOption('bucket'), $output);
		if (!$objectStore) {
			return self::FAILURE;
		}

		if (!$objectStore instanceof IObjectStoreMetaData) {
			$output->writeln('<error>Configured object store does currently not support listing objects</error>');
			return self::FAILURE;
		}
		$prefixLength = strlen('urn:oid:');

		$objects = $objectStore->listObjects('urn:oid:');
		$objects->rewind();
		$orphans = new \CallbackFilterIterator($objects, function (array $object) use ($prefixLength) {
			$fileId = (int)substr($object['urn'], $prefixLength);
			return !$this->fileIdInDb($fileId);
		});
		$orphans = new \ArrayIterator(iterator_to_array($orphans));
		$this->objectUtils->writeIteratorToOutput($input, $output, $orphans, self::CHUNK_SIZE);

		return self::SUCCESS;
	}

	private function fileIdInDb(int $fileId): bool {
		$this->query->setParameter('file_id', $fileId, IQueryBuilder::PARAM_INT);
		$result = $this->query->executeQuery();
		return $result->fetchOne() !== false;
	}
}
