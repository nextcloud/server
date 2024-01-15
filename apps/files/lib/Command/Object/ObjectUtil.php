<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023 Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files\Command\Object;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\ObjectStore\IObjectStore;
use OCP\IConfig;
use OCP\IDBConnection;
use Symfony\Component\Console\Output\OutputInterface;

class ObjectUtil {
	public function __construct(
		private IConfig $config,
		private IDBConnection $connection,
	) {
	}

	private function getObjectStoreConfig(): ?array {
		$config = $this->config->getSystemValue('objectstore_multibucket');
		if (is_array($config)) {
			$config['multibucket'] = true;
			return $config;
		}
		$config = $this->config->getSystemValue('objectstore');
		if (is_array($config)) {
			if (!isset($config['multibucket'])) {
				$config['multibucket'] = false;
			}
			return $config;
		}

		return null;
	}

	public function getObjectStore(?string $bucket, OutputInterface $output): ?IObjectStore {
		$config = $this->getObjectStoreConfig();
		if (!$config) {
			$output->writeln("<error>Instance is not using primary object store</error>");
			return null;
		}
		if ($config['multibucket'] && !$bucket) {
			$output->writeln("<error>--bucket option required</error> because <info>multi bucket</info> is enabled.");
			return null;
		}

		if (!isset($config['arguments'])) {
			throw new \Exception("no arguments configured for object store configuration");
		}
		if (!isset($config['class'])) {
			throw new \Exception("no class configured for object store configuration");
		}

		if ($bucket) {
			// s3, swift
			$config['arguments']['bucket'] = $bucket;
			// azure
			$config['arguments']['container'] = $bucket;
		}

		$store = new $config['class']($config['arguments']);
		if (!$store instanceof IObjectStore) {
			throw new \Exception("configured object store class is not an object store implementation");
		}
		return $store;
	}

	/**
	 * Check if an object is referenced in the database
	 */
	public function objectExistsInDb(string $object): int|false {
		if (!str_starts_with($object, 'urn:oid:')) {
			return false;
		}

		$fileId = (int)substr($object, strlen('urn:oid:'));
		$query = $this->connection->getQueryBuilder();
		$query->select('fileid')
			->from('filecache')
			->where($query->expr()->eq('fileid', $query->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)));
		$result = $query->executeQuery();

		if ($result->fetchOne() === false) {
			return false;
		}

		return $fileId;
	}
}
