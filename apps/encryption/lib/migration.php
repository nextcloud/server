<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Encryption;


use OC\DB\Connection;
use OC\Files\View;
use OCP\IConfig;

class Migration {

	private $moduleId;
	/** @var \OC\Files\View */
	private $view;
	/** @var \OC\DB\Connection */
	private $connection;
	/** @var IConfig */
	private $config;

	/**
	 * @param IConfig $config
	 * @param View $view
	 * @param Connection $connection
	 */
	public function __construct(IConfig $config, View $view, Connection $connection) {
		$this->view = $view;
		$this->view->getUpdater()->disable();
		$this->connection = $connection;
		$this->moduleId = \OCA\Encryption\Crypto\Encryption::ID;
		$this->config = $config;
	}

	public function __destruct() {
		$this->view->deleteAll('files_encryption/public_keys');
		$this->updateFileCache();
		$this->config->deleteAppValue('files_encryption', 'installed_version');
	}

	/**
	 * update file cache, copy unencrypted_size to the 'size' column
	 */
	private function updateFileCache() {
		$query = $this->connection->createQueryBuilder();
		$query->update('`*PREFIX*filecache`')
			->set('`size`', '`unencrypted_size`')
			->where($query->expr()->eq('`encrypted`', ':encrypted'))
			->setParameter('encrypted', 1);
		$query->execute();
	}

	/**
	 * iterate through users and reorganize the folder structure
	 */
	public function reorganizeFolderStructure() {
		$this->reorganizeSystemFolderStructure();

		$limit = 500;
		$offset = 0;
		do {
			$users = \OCP\User::getUsers('', $limit, $offset);
			foreach ($users as $user) {
				$this->reorganizeFolderStructureForUser($user);
			}
			$offset += $limit;
		} while (count($users) >= $limit);
	}

	/**
	 * reorganize system wide folder structure
	 */
	public function reorganizeSystemFolderStructure() {

		$this->createPathForKeys('/files_encryption');

		// backup system wide folders
		$this->backupSystemWideKeys();

		// rename system wide mount point
		$this->renameFileKeys('', '/files_encryption/keys');

		// rename system private keys
		$this->renameSystemPrivateKeys();

		$storage = $this->view->getMount('')->getStorage();
		$storage->getScanner()->scan('files_encryption');
	}


	/**
	 * reorganize folder structure for user
	 *
	 * @param string $user
	 */
	public function reorganizeFolderStructureForUser($user) {
		// backup all keys
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($user);
		if ($this->backupUserKeys($user)) {
			// rename users private key
			$this->renameUsersPrivateKey($user);
			$this->renameUsersPublicKey($user);
			// rename file keys
			$path = '/files_encryption/keys';
			$this->renameFileKeys($user, $path);
			$trashPath = '/files_trashbin/keys';
			if (\OC_App::isEnabled('files_trashbin') && $this->view->is_dir($user . '/' . $trashPath)) {
				$this->renameFileKeys($user, $trashPath, true);
				$this->view->deleteAll($trashPath);
			}
			// delete old folders
			$this->deleteOldKeys($user);
			$this->view->getMount('/' . $user)->getStorage()->getScanner()->scan('files_encryption');
		}
	}

	/**
	 * update database
	 */
	public function updateDB() {

		// delete left-over from old encryption which is no longer needed
		$this->config->deleteAppValue('files_encryption', 'ocsid');
		$this->config->deleteAppValue('files_encryption', 'types');
		$this->config->deleteAppValue('files_encryption', 'enabled');


		$query = $this->connection->createQueryBuilder();
		$query->update('`*PREFIX*appconfig`')
			->set('`appid`', ':newappid')
			->where($query->expr()->eq('`appid`', ':oldappid'))
			->setParameter('oldappid', 'files_encryption')
			->setParameter('newappid', 'encryption');
		$query->execute();

		$query = $this->connection->createQueryBuilder();
		$query->update('`*PREFIX*preferences`')
			->set('`appid`', ':newappid')
			->where($query->expr()->eq('`appid`', ':oldappid'))
			->setParameter('oldappid', 'files_encryption')
			->setParameter('newappid', 'encryption');
		$query->execute();
	}

	/**
	 * create backup of system-wide keys
	 */
	private function backupSystemWideKeys() {
		$backupDir = 'encryption_migration_backup_' . date("Y-m-d_H-i-s");
		$this->view->mkdir($backupDir);
		$this->view->copy('files_encryption', $backupDir . '/files_encryption');
	}

	/**
	 * create backup of user specific keys
	 *
	 * @param string $user
	 * @return bool
	 */
	private function backupUserKeys($user) {
		$encryptionDir = $user . '/files_encryption';
		if ($this->view->is_dir($encryptionDir)) {
			$backupDir = $user . '/encryption_migration_backup_' . date("Y-m-d_H-i-s");
			$this->view->mkdir($backupDir);
			$this->view->copy($encryptionDir, $backupDir);
			return true;
		}
		return false;
	}

	/**
	 * rename system-wide private keys
	 */
	private function renameSystemPrivateKeys() {
		$dh = $this->view->opendir('files_encryption');
		$this->createPathForKeys('/files_encryption/' . $this->moduleId );
		if (is_resource($dh)) {
			while (($privateKey = readdir($dh)) !== false) {
				if (!\OC\Files\Filesystem::isIgnoredDir($privateKey) ) {
					if (!$this->view->is_dir('/files_encryption/' . $privateKey)) {
						$this->view->rename('files_encryption/' . $privateKey, 'files_encryption/' . $this->moduleId . '/' . $privateKey);
						$this->renameSystemPublicKey($privateKey);
					}
				}
			}
			closedir($dh);
		}
	}

	/**
	 * rename system wide public key
	 *
	 * @param $privateKey private key for which we want to rename the corresponding public key
	 */
	private function renameSystemPublicKey($privateKey) {
		$publicKey = substr($privateKey,0 , strrpos($privateKey, '.privateKey')) . '.publicKey';
		$this->view->rename('files_encryption/public_keys/' . $publicKey, 'files_encryption/' . $this->moduleId . '/' . $publicKey);
	}

	/**
	 * rename user-specific private keys
	 *
	 * @param string $user
	 */
	private function renameUsersPrivateKey($user) {
		$oldPrivateKey = $user . '/files_encryption/' . $user . '.privateKey';
		$newPrivateKey = $user . '/files_encryption/' . $this->moduleId . '/' . $user . '.privateKey';
		$this->createPathForKeys(dirname($newPrivateKey));

		$this->view->rename($oldPrivateKey, $newPrivateKey);
	}

	/**
	 * rename user-specific public keys
	 *
	 * @param string $user
	 */
	private function renameUsersPublicKey($user) {
		$oldPublicKey = '/files_encryption/public_keys/' . $user . '.publicKey';
		$newPublicKey = $user . '/files_encryption/' . $this->moduleId . '/' . $user . '.publicKey';
		$this->createPathForKeys(dirname($newPublicKey));

		$this->view->rename($oldPublicKey, $newPublicKey);
	}

	/**
	 * rename file keys
	 *
	 * @param string $user
	 * @param string $path
	 * @param bool $trash
	 */
	private function renameFileKeys($user, $path, $trash = false) {

		$dh = $this->view->opendir($user . '/' . $path);

		if (is_resource($dh)) {
			while (($file = readdir($dh)) !== false) {
				if (!\OC\Files\Filesystem::isIgnoredDir($file)) {
					if ($this->view->is_dir($user . '/' . $path . '/' . $file)) {
						$this->renameFileKeys($user, $path . '/' . $file, $trash);
					} else {
						$target = $this->getTargetDir($user, $path, $file, $trash);
						$this->createPathForKeys(dirname($target));
						$this->view->rename($user . '/' . $path . '/' . $file, $target);
					}
				}
			}
			closedir($dh);
		}
	}

	/**
	 * generate target directory
	 *
	 * @param string $user
	 * @param string $filePath
	 * @param string $filename
	 * @param bool $trash
	 * @return string
	 */
	private function getTargetDir($user, $filePath, $filename, $trash) {
		if ($trash) {
			$targetDir = $user . '/files_encryption/keys/files_trashbin/' . substr($filePath, strlen('/files_trashbin/keys/')) . '/' . $this->moduleId . '/' . $filename;
		} else {
			$targetDir = $user . '/files_encryption/keys/files/' . substr($filePath, strlen('/files_encryption/keys/')) . '/' . $this->moduleId . '/' . $filename;
		}

		return $targetDir;
	}

	/**
	 * delete old keys
	 *
	 * @param string $user
	 */
	private function deleteOldKeys($user) {
		$this->view->deleteAll($user . '/files_encryption/keyfiles');
		$this->view->deleteAll($user . '/files_encryption/share-keys');
	}

	/**
	 * create directories for the keys recursively
	 *
	 * @param string $path
	 */
	private function createPathForKeys($path) {
		if (!$this->view->file_exists($path)) {
			$sub_dirs = explode('/', $path);
			$dir = '';
			foreach ($sub_dirs as $sub_dir) {
				$dir .= '/' . $sub_dir;
				if (!$this->view->is_dir($dir)) {
					$this->view->mkdir($dir);
				}
			}
		}
	}
}
