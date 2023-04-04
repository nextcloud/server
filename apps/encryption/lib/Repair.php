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

namespace OCA\Encryption;

use OC\Encryption\Keys\Storage;
use OC\Encryption\Manager;
use OC\Encryption\Util;
use OC\Files\Storage\Wrapper\Encryption;
use OC\Files\View;
use OCP\Encryption\IManager;
use OCP\Encryption\Keys\IStorage;
use OCP\Files\File;
use OCP\Files\Node;
use OCP\IUser;

class Repair {
	private Util $encryptionUtil;
	private string $keyRootDirectory;
	private View $rootView;
	private Manager $encryptionManager;
	private IStorage $keyStorage;

	public function __construct(
		Util $encryptionUtil,
		IManager $encryptionManager,
		IStorage $keyStorage
	) {
		$this->encryptionUtil = $encryptionUtil;
		$this->keyRootDirectory = rtrim($this->encryptionUtil->getKeyStorageRoot(), '/');
		$this->rootView = new View();
		// we're using some bits from the manager not exposed through the interface
		if (!$encryptionManager instanceof Manager) {
			throw new \Exception("Wrong encryption manager");
		}
		if (!$keyStorage instanceof Storage) {
			throw new \Exception("Wrong encryption storage");
		}
		$this->keyStorage = $keyStorage;
		$this->encryptionManager = $encryptionManager;
	}

	public function getSystemKeyRoot(): string {
		return $this->keyRootDirectory . '/files_encryption/keys';
	}

	public function getUserKeyRoot(IUser $user): string {
		return $this->keyRootDirectory . '/' . $user->getUID() . '/files_encryption/keys';
	}

	public function tryReadFile(File $node): bool {
		if ($this->keyStorage instanceof Storage) {
			$this->keyStorage->clearKeyCache();
		}
		try {
			$fh = $node->fopen('r');
			// read a single chunk
			$data = fread($fh, 8192);
			if ($data === false) {
				return false;
			} else {
				return true;
			}
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Get the contents of a file without decrypting it
	 *
	 * @param File $node
	 * @return resource
	 */
	public function openWithoutDecryption(File $node, string $mode) {
		$storage = $node->getStorage();
		$internalPath = $node->getInternalPath();
		if ($storage->instanceOfStorage(Encryption::class)) {
			/** @var Encryption $storage */
			try {
				$storage->setEnabled(false);
				$handle = $storage->fopen($internalPath, 'r');
				$storage->setEnabled(true);
			} catch (\Exception $e) {
				$storage->setEnabled(true);
				throw $e;
			}
		} else {
			$handle = $storage->fopen($internalPath, $mode);
		}
		/** @var resource|false $handle */
		if ($handle === false) {
			throw new \Exception("Failed to open " . $node->getPath());
		}
		return $handle;
	}

	/**
	 * Check if the data stored for a file is encrypted, regardless of it's metadata
	 *
	 * @param File $node
	 * @return bool
	 */
	public function isDataEncrypted(File $node): bool {
		$handle = $this->openWithoutDecryption($node, 'r');
		$firstBlock = fread($handle, $this->encryptionUtil->getHeaderSize());
		fclose($handle);

		$header = $this->encryptionUtil->parseRawHeader($firstBlock);
		return isset($header['oc_encryption_module']);
	}

	/**
	 * @param string $name
	 * @return \Generator<string>
	 */
	public function findAllKeysInDirectory(string $basePath) {
		if ($this->rootView->is_dir($basePath . '/OC_DEFAULT_MODULE')) {
			yield $basePath;
		} else {
			/** @var false|resource $dh */
			$dh = $this->rootView->opendir($basePath);
			if (!$dh) {
				throw new \Exception("Invalid base path " . $basePath);
			}
			while ($child = readdir($dh)) {
				if ($child != '..' && $child != '.') {
					$childPath = $basePath . '/' . $child;

					// recurse if the child is not a key folder
					if ($this->rootView->is_dir($childPath) && !is_dir($childPath . '/OC_DEFAULT_MODULE')) {
						yield from $this->findAllKeysInDirectory($childPath);
					}
				}
			}
		}
	}

	/**
	 * Test if the provided key is valid as a system key for the file
	 *
	 * @param string $key
	 * @param File $node
	 * @return bool
	 */
	public function testSystemKey(string $key, File $node): bool {
		$systemKeyPath = $this->getSystemKeyPath($node);

		if ($this->rootView->file_exists($systemKeyPath)) {
			// already has a key, reject new key
			return false;
		}

		$this->rootView->copy($key, $systemKeyPath);
		$isValid = $this->tryReadFile($node);
		$this->rootView->rmdir($systemKeyPath);
		return $isValid;
	}

	/**
	 * Test if the provided key is valid as a system key for the file
	 *
	 * @param IUser $user
	 * @param string $key
	 * @param File $node
	 * @return bool
	 */
	public function testUserKey(IUser $user, string $key, File $node): bool {
		$userKeyPath = $this->getUserKeyPath($user, $node);

		if ($this->rootView->file_exists($userKeyPath)) {
			// already has a key, reject new key
			return false;
		}

		$this->rootView->copy($key, $userKeyPath);
		$isValid = $this->tryReadFile($node);
		$this->rootView->rmdir($userKeyPath);
		return $isValid;
	}

	/**
	 * Decrypt a file with the specified system key and mark the key as not-encrypted
	 *
	 * @param File $node
	 * @param string $key
	 * @return void
	 */
	public function decryptWithSystemKey(File $node, string $key): void {
		$storage = $node->getStorage();
		$name = $node->getName();

		$node->move($node->getPath() . '.bak');
		$systemKeyPath = $this->getSystemKeyPath($node);
		$this->rootView->copy($key, $systemKeyPath);

		try {
			if (!$storage->instanceOfStorage(Encryption::class)) {
				$storage = $this->encryptionManager->forceWrapStorage($node->getMountPoint(), $storage);
			}
			/** @var false|resource $source */
			$source = $storage->fopen($node->getInternalPath(), 'r');
			if (!$source) {
				throw new \Exception("Failed to open " . $node->getPath() . " with " . $key);
			}
			$decryptedNode = $node->getParent()->newFile($name);

			$target = $this->openWithoutDecryption($decryptedNode, 'w');
			stream_copy_to_stream($source, $target);
			fclose($target);
			fclose($source);

			$decryptedNode->getStorage()->getScanner()->scan($decryptedNode->getInternalPath());
		} catch (\Exception $e) {
			$this->rootView->rmdir($systemKeyPath);

			// remove the .bak
			$node->move(substr($node->getPath(), 0, -4));

			throw $e;
		}

		if ($this->isDataEncrypted($decryptedNode)) {
			throw new \Exception($node->getPath() . " still encrypted after attempting to decrypt with " . $key);
		}

		$this->markAsUnEncrypted($decryptedNode);

		$this->rootView->rmdir($systemKeyPath);
	}

	public function markAsUnEncrypted(Node $node): void {
		$node->getStorage()->getCache()->update($node->getId(), ['encrypted' => 0]);
	}

	public function getSystemKeyPath(Node $node): string {
		$path = $this->getUserRelativePath($node->getPath());
		return $this->getSystemKeyRoot() . $path . '/';
	}

	public function getUserKeyPath(IUser $user, Node $node): string {
		$path = $this->getUserRelativePath($node->getPath());
		return $this->getUserKeyRoot($user) . '/' . $path . '/';
	}

	public function getKeyPath(IUser $user, Node $node): string {
		if ($this->needsSystemKey($node->getPath())) {
			return $this->getSystemKeyPath($node);
		} else {
			return $this->getUserKeyRoot($user);
		}
	}

	public function hasSystemKey(Node $node): bool {
		// this uses View instead of the RootFolder because the keys might not be in the cache
		return $this->rootView->file_exists($this->getSystemKeyPath($node));
	}

	public function hasUserKey(IUser $user, Node $node): bool {
		// this uses View instead of the RootFolder because the keys might not be in the cache
		return $this->rootView->file_exists($this->getUserKeyPath($user, $node));
	}

	private function getUserRelativePath(string $path): string {
		$parts = explode('/', $path, 3);
		if (count($parts) >= 3) {
			return '/' . $parts[2];
		} else {
			return '';
		}
	}

	public function needsSystemKey(string $path): bool {
		[, $uid, $path] = explode("/", $path, 3);
		$path = '/' . $path;
		return $this->encryptionUtil->isSystemWideMountPoint($path, $uid);
	}
}
