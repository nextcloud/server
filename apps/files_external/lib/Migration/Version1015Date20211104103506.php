<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Migration;

use Closure;
use OC\Files\Cache\Storage;
use OCP\DB\Exception;
use OCP\DB\IResult;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Psr\Log\LoggerInterface;

class Version1015Date20211104103506 extends SimpleMigrationStep {

	public function __construct(
		private IDBConnection $connection,
		private LoggerInterface $logger,
	) {
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$qb = $this->connection->getQueryBuilder();
		$qb->update('storages')
			->set('id', $qb->createParameter('newId'))
			->where($qb->expr()->eq('id', $qb->createParameter('oldId')));

		$mounts = $this->getS3Mounts();
		if (!$mounts instanceof IResult) {
			throw new \Exception('Could not fetch existing mounts for migration');
		}

		while ($mount = $mounts->fetch()) {
			$config = $this->getStorageConfig((int)$mount['mount_id']);
			$hostname = $config['hostname'];
			$bucket = $config['bucket'];
			$key = $config['key'];
			$oldId = Storage::adjustStorageId('amazon::' . $bucket);
			$newId = Storage::adjustStorageId('amazon::external::' . md5($hostname . ':' . $bucket . ':' . $key));
			try {
				$qb->setParameter('oldId', $oldId);
				$qb->setParameter('newId', $newId);
				$qb->execute();
				$this->logger->info('Migrated s3 storage id for mount with id ' . $mount['mount_id'] . ' to ' . $newId);
			} catch (Exception $e) {
				$this->logger->error('Failed to migrate external s3 storage id for mount with id ' . $mount['mount_id'], [
					'exception' => $e
				]);
			}
		}
		return null;
	}

	/**
	 * @throws Exception
	 * @return IResult|int
	 */
	private function getS3Mounts() {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('m.mount_id')
			->selectAlias('c.value', 'bucket')
			->from('external_mounts', 'm')
			->innerJoin('m', 'external_config', 'c', 'c.mount_id = m.mount_id')
			->where($qb->expr()->eq('m.storage_backend', $qb->createPositionalParameter('amazons3')))
			->andWhere($qb->expr()->eq('c.key', $qb->createPositionalParameter('bucket')));
		return $qb->execute();
	}

	/**
	 * @throws Exception
	 */
	private function getStorageConfig(int $mountId): array {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('key', 'value')
			->from('external_config')
			->where($qb->expr()->eq('mount_id', $qb->createPositionalParameter($mountId)));
		$config = [];
		foreach ($qb->execute()->fetchAll() as $row) {
			$config[$row['key']] = $row['value'];
		}
		return $config;
	}
}
