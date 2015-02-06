<?php
 /**
 * ownCloud
 *
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * @author Bjoern Schiessle <schiessle@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files_Encryption;


class Migration {

	/**
	 * @var \OC\Files\View
	 */
	private $view;
	private $public_share_key_id;
	private $recovery_key_id;

	public function __construct() {
		$this->view = new \OC\Files\View();
		$this->public_share_key_id = Helper::getPublicShareKeyId();
		$this->recovery_key_id = Helper::getRecoveryKeyId();
	}

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
		} while(count($users) >= $limit);
	}

	public function reorganizeSystemFolderStructure() {

		$this->createPathForKeys('/files_encryption');

		// backup system wide folders
		$this->backupSystemWideKeys();

		// rename public keys
		$this->renamePublicKeys();

		// rename system wide mount point
		$this->renameFileKeys('', '/files_encryption/keyfiles');

		// rename system private keys
		$this->renameSystemPrivateKeys();

		// delete old system wide folders
		$this->view->deleteAll('/public-keys');
		$this->view->deleteAll('/owncloud_private_key');
		$this->view->deleteAll('/files_encryption/share-keys');
		$this->view->deleteAll('/files_encryption/keyfiles');
	}


	public function reorganizeFolderStructureForUser($user) {
		// backup all keys
		\OC_Util::setupFS($user);
		if ($this->backupUserKeys($user)) {
			// create new 'key' folder
			$this->view->mkdir($user . '/files_encryption/keys');
			// rename users private key
			$this->renameUsersPrivateKey($user);
			// rename file keys
			$path = $user . '/files_encryption/keyfiles';
			$this->renameFileKeys($user, $path);
			$trashPath = $user . '/files_trashbin/keyfiles';
			if (\OC_App::isEnabled('files_trashbin') && $this->view->is_dir($trashPath)) {
				$this->renameFileKeys($user, $trashPath, true);
				$this->view->deleteAll($trashPath);
				$this->view->deleteAll($user . '/files_trashbin/share-keys');
			}
			// delete old folders
			$this->deleteOldKeys($user);
		}
	}

	private function backupSystemWideKeys() {
		$backupDir = 'encryption_migration_backup_' . date("Y-m-d_H-i-s");
		$this->view->mkdir($backupDir);
		$this->view->copy('owncloud_private_key', $backupDir . '/owncloud_private_key');
		$this->view->copy('public-keys', $backupDir . '/public-keys');
		$this->view->copy('files_encryption', $backupDir . '/files_encryption');
	}

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

	private function renamePublicKeys() {
		$dh = $this->view->opendir('public-keys');

		$this->createPathForKeys('files_encryption/public_keys');

		if (is_resource($dh)) {
			while (($oldPublicKey = readdir($dh)) !== false) {
				if (!\OC\Files\Filesystem::isIgnoredDir($oldPublicKey)) {
					$newPublicKey = substr($oldPublicKey, 0, strlen($oldPublicKey) - strlen('.public.key')) . '.publicKey';
					$this->view->rename('public-keys/' . $oldPublicKey , 'files_encryption/public_keys/' . $newPublicKey);
				}
			}
			closedir($dh);
		}
	}

	private function renameSystemPrivateKeys() {
		$dh = $this->view->opendir('owncloud_private_key');

		if (is_resource($dh)) {
			while (($oldPrivateKey = readdir($dh)) !== false) {
				if (!\OC\Files\Filesystem::isIgnoredDir($oldPrivateKey)) {
					$newPrivateKey = substr($oldPrivateKey, 0, strlen($oldPrivateKey) - strlen('.private.key')) . '.privateKey';
					$this->view->rename('owncloud_private_key/' . $oldPrivateKey , 'files_encryption/' . $newPrivateKey);
				}
			}
			closedir($dh);
		}
	}

	private function renameUsersPrivateKey($user) {
		 $oldPrivateKey = $user . '/files_encryption/' . $user . '.private.key';
		 $newPrivateKey = substr($oldPrivateKey, 0, strlen($oldPrivateKey) - strlen('.private.key')) . '.privateKey';

		 $this->view->rename($oldPrivateKey, $newPrivateKey);
	}

	private function getFileName($file, $trash) {

		$extLength = strlen('.key');

		if ($trash) {
			$parts = explode('.', $file);
			if ($parts[count($parts) - 1] !== 'key') {
				$extLength = $extLength + strlen('.' . $parts[count($parts) - 1]);
			}
		}

		$filename = substr($file, 0, strlen($file) - $extLength);

		return $filename;
	}

	private function getExtension($file, $trash) {

		$extension = '';

		if ($trash) {
			$parts = explode('.', $file);
			if ($parts[count($parts) - 1] !== 'key') {
				$extension = '.' . $parts[count($parts) - 1];
			}
		}

		return $extension;
	}

	private function getFilePath($path, $user, $trash) {
		$offset = $trash ? strlen($user . '/files_trashbin/keyfiles') :  strlen($user . '/files_encryption/keyfiles');
		return substr($path, $offset);
	}

	private function getTargetDir($user, $filePath, $filename, $extension, $trash) {
		if ($trash) {
			$targetDir = $user . '/files_trashbin/keys/' . $filePath . '/' . $filename . $extension;
		} else {
			$targetDir = $user . '/files_encryption/keys/' . $filePath . '/' . $filename . $extension;
		}

		return $targetDir;
	}

	private function renameFileKeys($user, $path, $trash = false) {

		$dh = $this->view->opendir($path);

		if (is_resource($dh)) {
			while (($file = readdir($dh)) !== false) {
				if (!\OC\Files\Filesystem::isIgnoredDir($file)) {
					if ($this->view->is_dir($path . '/' . $file)) {
						$this->renameFileKeys($user, $path . '/' . $file, $trash);
					} else {
						$filename = $this->getFileName($file, $trash);
						$filePath = $this->getFilePath($path, $user, $trash);
						$extension = $this->getExtension($file, $trash);
						$targetDir = $this->getTargetDir($user, $filePath, $filename, $extension, $trash);
						$this->createPathForKeys($targetDir);
						$this->view->copy($path . '/' . $file, $targetDir . '/fileKey');
						$this->renameShareKeys($user, $filePath, $filename, $targetDir, $trash);
					}
				}
			}
			closedir($dh);
		}
	}

	private function getOldShareKeyPath($user, $filePath, $trash) {
		if ($trash) {
			$oldShareKeyPath = $user . '/files_trashbin/share-keys/' . $filePath;
		} else {
			$oldShareKeyPath = $user . '/files_encryption/share-keys/' . $filePath;
		}

		return $oldShareKeyPath;
	}

	private function getUidFromShareKey($file, $filename, $trash) {
		$extLength = strlen('.shareKey');
		if ($trash) {
			$parts = explode('.', $file);
			if ($parts[count($parts) - 1] !== 'shareKey') {
				$extLength = $extLength + strlen('.' . $parts[count($parts) - 1]);
			}
		}

		$uid = substr($file, strlen($filename) + 1, $extLength * -1);

		return $uid;
	}

	private function renameShareKeys($user, $filePath, $filename, $target, $trash) {
		$oldShareKeyPath = $this->getOldShareKeyPath($user, $filePath, $trash);
		$dh = $this->view->opendir($oldShareKeyPath);

		if (is_resource($dh)) {
			while (($file = readdir($dh)) !== false) {
				if (!\OC\Files\Filesystem::isIgnoredDir($file)) {
					if ($this->view->is_dir($oldShareKeyPath . '/' . $file)) {
						continue;
					} else {
						if (substr($file, 0, strlen($filename) +1) === $filename . '.') {

							$uid = $this->getUidFromShareKey($file, $filename, $trash);
							$this->view->copy($oldShareKeyPath . '/' . $file, $target . '/' . $uid . '.shareKey');
						}
					}

				}
			}
			closedir($dh);
		}
	}

	private function deleteOldKeys($user) {
		$this->view->deleteAll($user . '/files_encryption/keyfiles');
		$this->view->deleteAll($user . '/files_encryption/share-keys');
	}

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
