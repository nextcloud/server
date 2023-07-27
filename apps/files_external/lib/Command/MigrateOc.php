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
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Security\ICrypto;
use phpseclib\Crypt\AES;
use phpseclib\Crypt\Hash;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateOc extends Base {
	private IDBConnection $connection;
	private IConfig $config;
	private ICrypto $crypto;

	public const ALL = -1;

	public function __construct(
		IDBConnection $connection,
		IConfig $config,
		ICrypto $crypto,
	) {
		parent::__construct();
		$this->connection = $connection;
		$this->config = $config;
		$this->crypto = $crypto;
	}

	protected function configure(): void {
		$this
			->setName('files_external:migrate-oc')
			->setDescription('Migrate external storages when moving from ownCloud')
			->addOption("dry-run", null, InputOption::VALUE_NONE, "Don't save any modifications, only try the migration");
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$configs = $this->getWndConfigs();
		$dryRun = $input->getOption('dry-run');

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
			if (!$dryRun) {
				if (!$this->setStorageId($wndStorageId, $storageId)) {
					$output->writeln("<error>No WMD storage with id $wndStorageId found</error>");
					return 1;
				}
			}
		}

		if (count($configs) && !$dryRun) {
			$this->migrateWndBackend();

			$output->writeln("Successfully migrated");
		}

		$passwords = $this->getV2StoragePasswords();

		if (count($passwords)) {
			$output->writeln("Found <info>" . count($passwords) . "</info> stored passwords that need re-encoding");
			foreach ($passwords as $id => $password) {
				$decoded = $this->decodePassword($password);
				if (!$dryRun) {
					$this->setStorageConfig($id, $this->encryptPassword($decoded));
				}
			}
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

	/**
	 * @return array<int, string>
	 */
	private function getV2StoragePasswords(): array {
		$query = $this->connection->getQueryBuilder();
		$query->select('config_id', 'value')
			->from('external_config')
			->where($query->expr()->eq('key', $query->createNamedParameter('password')))
			->andWhere($query->expr()->like('value', $query->createNamedParameter('v2|%')));

		$rows = $query->executeQuery()->fetchAll();
		$configs = [];
		foreach ($rows as $row) {
			$configs[(int)$row['config_id']] = $row['value'];
		}
		return $configs;
	}

	private function setStorageConfig(int $id, string $value) {
		$query = $this->connection->getQueryBuilder();
		$query->update('external_config')
			->set('value', $query->createNamedParameter($value))
			->where($query->expr()->eq('config_id', $query->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		$query->executeStatement();
	}

	private function setStorageId(string $old, string $new): bool {
		$query = $this->connection->getQueryBuilder();
		$query->update('storages')
			->set('id', $query->createNamedParameter($new))
			->where($query->expr()->eq('id', $query->createNamedParameter($old)));
		return $query->executeStatement() > 0;
	}

	/**
	 * Decrypt a password from the ownCloud scheme
	 *
	 * @param string $encoded
	 * @return string
	 * @throws \Exception
	 * @psalm-suppress InternalMethod
	 */
	private function decodePassword(string $encoded): string {
		if (str_starts_with($encoded, 'v2')) {
			// see https://github.com/owncloud/core/blob/89c5c364b8fa39b011c89fbfad779b547a333a92/lib/private/Security/Crypto.php#L129
			$parts = \explode('|', $encoded);
			$cipher = new AES();
			$password = $this->config->getSystemValue('secret');
			$derived = \hash_hkdf('sha512', $password, 0);
			[$password, $hmacKey] = \str_split($derived, 32);
			$cipher->setPassword($password);

			$ciphertext = \hex2bin($parts[1]);
			$iv = \hex2bin($parts[2]);
			$hmac = \hex2bin($parts[3]);

			$cipher->setIV($iv);

			if (!\hash_equals($this->calculateHMAC($parts[1] . $iv, $hmacKey), $hmac)) {
				throw new \Exception('HMAC does not match while attempting to re-encode password.');
			}

			return $cipher->decrypt($ciphertext);
		} else {
			return $this->crypto->decrypt($encoded);
		}
	}

	private function calculateHMAC(string $message, string $password): string {
		// Append an "a" behind the password and hash it to prevent reusing the same password as for encryption
		$password = \hash('sha512', $password . 'a');

		$hash = new Hash('sha512');
		$hash->setKey($password);
		return $hash->hash($message);
	}

	/**
	 * Encrypt a password in the Nextcloud scheme
	 */
	private function encryptPassword(string $password): string {
		return $this->crypto->encrypt($password);
	}
}
