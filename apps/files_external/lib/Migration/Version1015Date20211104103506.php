<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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

	/** @var IDBConnection */
	private $connection;
	/** @var LoggerInterface */
	private $logger;

	public function __construct(IDBConnection $connection, LoggerInterface $logger) {
		$this->connection = $connection;
		$this->logger = $logger;
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
	 * @return \OCP\DB\IResult|int
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
