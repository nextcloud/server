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

namespace OCA\Files_External\Command;

use OC\Core\Command\Base;
use OCA\Files_External\Lib\Storage\SMB;
use OCP\IDBConnection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateOc extends Base {
	private IDBConnection $connection;

	public const ALL = -1;

	public function __construct(
		IDBConnection $connection
	) {
		parent::__construct();
		$this->connection = $connection;
	}

	protected function configure(): void {
		$this
			->setName('files_external:migrate-oc')
			->setDescription('Migrate external storages when moving from ownCloud');
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$configs = $this->getWndConfigs();

		$output->writeln("Found <info>" . count($configs) . "</info> wnd storages");

		foreach ($configs as $config) {
			if (!isset($config['user'])) {
				$output->writeln("<error>Only basic username password authentication is currently supported</error>");
				return 1;
			}

			if (isset($config['root']) && $config['root'] !== '' && $config['root'] !== '/') {
				$root = '/' . trim($config['root'], '/') . '/';
			} else {
				$root = '/';
			}

			if (isset($config['domain']) && $config['domain'] !== ""
				&& \strpos($config['user'], "\\") === false && \strpos($config['user'], "/") === false
			) {
				$usernameWithDomain = $config['domain'] . "\\" . $config['user'];
			} else {
				$usernameWithDomain = $config['user'];
			}
			$wndStorageId = "wnd::{$usernameWithDomain}@{$config['host']}/{$config['share']}/{$root}";

			$storage = new SMB($config);
			$storageId = $storage->getId();
			if (!$this->setStorageId($wndStorageId, $storageId)) {
				$output->writeln("<error>No WMD storage with id $wndStorageId found</error>");
				return 1;
			}
		}

		if (count($configs)) {
			$this->migrateWndBackend();

			$output->writeln("Successfully migrated");
		}

		return 0;
	}

	private function migrateWndBackend(): int {
		$query = $this->connection->getQueryBuilder();
		$query->update('external_mounts')
			->set('storage_backend', $query->createNamedParameter('smb'))
			->where($query->expr()->eq('storage_backend', $query->createNamedParameter('windows_network_drive')));
		return $query->executeStatement();
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	private function getWndConfigs(): array {
		$query = $this->connection->getQueryBuilder();
		$query->select('c.mount_id', 'key', 'value')
			->from('external_config', 'c')
			->innerJoin('c', 'external_mounts', 'm', $query->expr()->eq('c.mount_id', 'm.mount_id'))
			->where($query->expr()->eq('storage_backend', $query->createNamedParameter('windows_network_drive')));

		$rows = $query->executeQuery()->fetchAll();
		$configs = [];
		foreach ($rows as $row) {
			$mountId = (int)$row['mount_id'];
			if (!isset($configs[$mountId])) {
				$configs[$mountId] = [];
			}
			$configs[$mountId][$row['key']] = $row['value'];
		}
		return $configs;
	}

	private function setStorageId(string $old, string $new): bool {
		$query = $this->connection->getQueryBuilder();
		$query->update('storages')
			->set('id', $query->createNamedParameter($new))
			->where($query->expr()->eq('id', $query->createNamedParameter($old)));
		return $query->executeStatement() > 0;
	}
}
