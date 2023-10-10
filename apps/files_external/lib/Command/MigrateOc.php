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
		ICrypto $crypto
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
		$dryRun = $input->getOption('dry-run');

		$this->migrateStorageConfigPasswords($dryRun, $output);
		$this->migrateStorageCredentials($dryRun, $output);
		$this->migrateWndStorage($dryRun, $output);
		$this->migrateWndExternalStorages($dryRun, $output);

		return 0;
	}

	private function migrateWndStorage(bool $dryRun, OutputInterface $output): void {
		$configs = $this->getWndExternalStorageConfigs();
		$output->writeln("Found <info>" . count($configs) . "</info> wnd storages");

		foreach ($configs as $config) {
			$output->writeln("<info>" . $config['host'] . ' - ' . $config['auth_backend'] . "</info>");

			switch($config['auth_backend']) {
				case 'password::password':
					$this->migrateWndStorageWithCredentials($dryRun, $output, $config);
					break;
				case 'password::sessioncredentials':
					// Impossible to do as credentials are stored in memory.
					$output->writeln("	<error>Cannot migrate storages authenticated by sessions credentials</error>");
					break;
				case 'password::logincredentials':
					$sessionCredentials = $this->getStorageCredentialsWithIdentifier($config['auth_backend'].'/credentials');
					foreach ($sessionCredentials as $credentials) {
						$config['user'] = $credentials['user'];
						$config['password'] = $credentials['password'];
						$this->migrateWndStorageWithCredentials($dryRun, $output, $config);
					}
					break;
				case 'password::userprovided':
					$sessionCredentials = $this->getStorageCredentialsWithIdentifier($config['auth_backend'].'/'.$config['mount_id']);
					foreach ($sessionCredentials as $credentials) {
						$config['user'] = $credentials['user'];
						$config['password'] = $credentials['password'];
						$this->migrateWndStorageWithCredentials($dryRun, $output, $config);
					}
					break;
				case 'password::global':
					$sessionCredentials = $this->getStorageCredentialsWithIdentifier($config['auth_backend']);
					foreach ($sessionCredentials as $credentials) {
						$config['user'] = $credentials['user'];
						$config['password'] = $credentials['password'];
						$this->migrateWndStorageWithCredentials($dryRun, $output, $config);
					}
					break;
				case 'password::hardcodedconfigcredentials':
					$output->writeln("	<error>Cannot migrate storages authenticated by hard coded credentials</error>");
					break;
				case 'kerberos::kerberos':
					// Impossible to do as credentials are stored in memory.
					$output->writeln("	<error>Cannot migrate storages authenticated by kerberos</error>");
					continue 2;
					break;
				default:
					echo "UNSUPPORTED AUTH BACKEND !";
					continue 2;
			}
		}
	}

	private function migrateWndStorageWithCredentials(bool $dryRun, OutputInterface $output, array $config): void {
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

		$query = $this->connection->getQueryBuilder();
		$rows = $query->select('id')
			->from('storages')
			->where($query->expr()->eq('id', $query->createNamedParameter($wndStorageId)))
			->executeQuery()
			->fetchAll();

		if (count($rows) === 1) {
			$output->writeln("	- Found one storage $wndStorageId");
			if (!$dryRun && !$this->setStorageId($wndStorageId, $storageId)) {
					$output->writeln("<error>Failed to update WMD storage with id $wndStorageId</error>");
			}
		} elseif (count($rows) > 1) {
			$output->writeln("<error>More than one storage found $wndStorageId</error>");
		}
	}

	private function migrateWndExternalStorages(bool $dryRun, OutputInterface $output): void {
		$query = $this->connection->getQueryBuilder();
		$rows = $query->select('mount_id')
				->from('external_mounts')
				->where($query->expr()->eq('storage_backend', $query->createNamedParameter('windows_network_drive')))
				->executeQuery()
				->fetchAll();

		$output->writeln("Found <info>" . count($rows) . "</info> wnd external storages");

		if (count($rows) > 0 && !$dryRun) {
			$query = $this->connection->getQueryBuilder();
			$query->update('external_mounts')
				->set('storage_backend', $query->createNamedParameter('smb'))
				->where($query->expr()->eq('storage_backend', $query->createNamedParameter('windows_network_drive')))
				->executeStatement();
		}
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	private function getWndExternalStorageConfigs(): array {
		$query = $this->connection->getQueryBuilder();
		$query->select('c.mount_id', 'key', 'value', 'm.auth_backend')
			->from('external_config', 'c')
			->innerJoin('c', 'external_mounts', 'm', $query->expr()->eq('c.mount_id', 'm.mount_id'))
			->where($query->expr()->eq('storage_backend', $query->createNamedParameter('windows_network_drive')));

		$rows = $query->executeQuery()->fetchAll();
		$configs = [];
		foreach ($rows as $row) {
			$mountId = (int)$row['mount_id'];
			if (!isset($configs[$mountId])) {
				$configs[$mountId] = [];
				$configs[$mountId]['mount_id'] = $row['mount_id'];
				$configs[$mountId]['auth_backend'] = $row['auth_backend'];
			}
			$configs[$mountId][$row['key']] = $row['value'];
		}
		return $configs;
	}

	/**
	 * @return array<int, string>
	 */
	private function getStorageConfigPasswords(): array {
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

	private function migrateStorageConfigPasswords(bool $dryRun, OutputInterface $output): void {
		$passwords = $this->getStorageConfigPasswords();
		$output->writeln("Found <info>" . count($passwords) . "</info> config passwords that need re-encoding");

		if (count($passwords)) {
			foreach ($passwords as $id => $password) {
				$decoded = $this->decodePassword($password);
				if (!$dryRun) {
					$this->setStorageConfig($id, $this->encryptPassword($decoded));
				}
			}
		}
	}

	private function setStorageConfig(int $id, string $value): void {
		$query = $this->connection->getQueryBuilder();
		$query->update('external_config')
			->set('value', $query->createNamedParameter($value))
			->where($query->expr()->eq('config_id', $query->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		$query->executeStatement();
	}

	/**
	 * @return array<array<string, string>>
	 */
	private function getStorageCredentials(): array {
		$query = $this->connection->getQueryBuilder();
		$query->select('id', 'user', 'identifier', 'credentials')
			->from('storages_credentials')
			->where($query->expr()->like('credentials', $query->createNamedParameter('v2|%')));

		return $query->executeQuery()->fetchAll();
	}

	/**
	 * @return array<array<string, string>>
	 */
	private function getStorageCredentialsWithIdentifier(string $identifier): array {
		$query = $this->connection->getQueryBuilder();
		$query->select('credentials')
			->from('storages_credentials')
			->where($query->expr()->eq('identifier', $query->createNamedParameter($identifier)));

		$rows = $query->executeQuery()->fetchAll();

		return array_map(fn ($row): array => json_decode($this->crypto->decrypt($row['credentials']), true), $rows);
	}

	private function migrateStorageCredentials(bool $dryRun, OutputInterface $output): void {
		$passwords = $this->getStorageCredentials();
		$output->writeln("Found <info>" . count($passwords) . "</info> stored credentials that need re-encoding");

		if (count($passwords)) {
			foreach ($passwords as $passwordRow) {
				$decoded = $this->decodePassword($passwordRow["credentials"]);
				if (!$dryRun) {
					$this->setStorageCredentials($passwordRow['id'], $this->encryptPassword($decoded));
				}
			}
		}
	}

	private function setStorageCredentials(string $id, string $encryptedPassword): void {
		$query = $this->connection->getQueryBuilder();

		$query->update('storages_credentials')
			->set('credentials', $query->createNamedParameter($encryptedPassword))
			->where($query->expr()->eq('id', $query->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->executeStatement();
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
