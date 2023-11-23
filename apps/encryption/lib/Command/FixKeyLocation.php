<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Robin Appelman <robin@icewind.nl>
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

namespace OCA\Encryption\Command;

use OC\Encryption\Manager;
use OC\Encryption\Util;
use OC\Files\Storage\Wrapper\Encryption;
use OC\Files\View;
use OCP\Encryption\IManager;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixKeyLocation extends Command {
	private IUserManager $userManager;
	private IUserMountCache $userMountCache;
	private Util $encryptionUtil;
	private IRootFolder $rootFolder;
	private string $keyRootDirectory;
	private View $rootView;
	private Manager $encryptionManager;

	public function __construct(
		IUserManager $userManager,
		IUserMountCache $userMountCache,
		Util $encryptionUtil,
		IRootFolder $rootFolder,
		IManager $encryptionManager
	) {
		$this->userManager = $userManager;
		$this->userMountCache = $userMountCache;
		$this->encryptionUtil = $encryptionUtil;
		$this->rootFolder = $rootFolder;
		$this->keyRootDirectory = rtrim($this->encryptionUtil->getKeyStorageRoot(), '/');
		$this->rootView = new View();
		if (!$encryptionManager instanceof Manager) {
			throw new \Exception("Wrong encryption manager");
		}
		$this->encryptionManager = $encryptionManager;

		parent::__construct();
	}


	protected function configure(): void {
		parent::configure();

		$this
			->setName('encryption:fix-key-location')
			->setDescription('Fix the location of encryption keys for external storage')
			->addOption('dry-run', null, InputOption::VALUE_NONE, "Only list files that require key migration, don't try to perform any migration")
			->addArgument('user', InputArgument::REQUIRED, "User id to fix the key locations for");
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$dryRun = $input->getOption('dry-run');
		$userId = $input->getArgument('user');
		$user = $this->userManager->get($userId);
		if (!$user) {
			$output->writeln("<error>User $userId not found</error>");
			return 1;
		}

		\OC_Util::setupFS($user->getUID());

		$mounts = $this->getSystemMountsForUser($user);
		foreach ($mounts as $mount) {
			$mountRootFolder = $this->rootFolder->get($mount->getMountPoint());
			if (!$mountRootFolder instanceof Folder) {
				$output->writeln("<error>System wide mount point is not a directory, skipping: " . $mount->getMountPoint() . "</error>");
				continue;
			}

			$files = $this->getAllEncryptedFiles($mountRootFolder);
			foreach ($files as $file) {
				/** @var File $file */
				$hasSystemKey = $this->hasSystemKey($file);
				$hasUserKey = $this->hasUserKey($user, $file);
				if (!$hasSystemKey) {
					if ($hasUserKey) {
						// key was stored incorrectly as user key, migrate

						if ($dryRun) {
							$output->writeln("<info>" . $file->getPath() . "</info> needs migration");
						} else {
							$output->write("Migrating key for <info>" . $file->getPath() . "</info> ");
							if ($this->copyUserKeyToSystemAndValidate($user, $file)) {
								$output->writeln("<info>✓</info>");
							} else {
								$output->writeln("<fg=red>❌</>");
								$output->writeln("  Failed to validate key for <error>" . $file->getPath() . "</error>, key will not be migrated");
							}
						}
					} else {
						// no matching key, probably from a broken cross-storage move

						$shouldBeEncrypted = $file->getStorage()->instanceOfStorage(Encryption::class);
						$isActuallyEncrypted = $this->isDataEncrypted($file);
						if ($isActuallyEncrypted) {
							if ($dryRun) {
								if ($shouldBeEncrypted) {
									$output->write("<info>" . $file->getPath() . "</info> needs migration");
								} else {
									$output->write("<info>" . $file->getPath() . "</info> needs decryption");
								}
								$foundKey = $this->findUserKeyForSystemFile($user, $file);
								if ($foundKey) {
									$output->writeln(", valid key found at <info>" . $foundKey . "</info>");
								} else {
									$output->writeln(" <error>❌ No key found</error>");
								}
							} else {
								if ($shouldBeEncrypted) {
									$output->write("<info>Migrating key for " . $file->getPath() . "</info>");
								} else {
									$output->write("<info>Decrypting " . $file->getPath() . "</info>");
								}
								$foundKey = $this->findUserKeyForSystemFile($user, $file);
								if ($foundKey) {
									if ($shouldBeEncrypted) {
										$systemKeyPath = $this->getSystemKeyPath($file);
										$this->rootView->copy($foundKey, $systemKeyPath);
										$output->writeln("  Migrated key from <info>" . $foundKey . "</info>");
									} else {
										$this->decryptWithSystemKey($file, $foundKey);
										$output->writeln("  Decrypted with key from <info>" . $foundKey . "</info>");
									}
								} else {
									$output->writeln(" <error>❌ No key found</error>");
								}
							}
						} else {
							if ($dryRun) {
								$output->writeln("<info>" . $file->getPath() . " needs to be marked as not encrypted</info>");
							} else {
								$this->markAsUnEncrypted($file);
								$output->writeln("<info>" . $file->getPath() . " marked as not encrypted</info>");
							}
						}
					}
				}
			}
		}

		return 0;
	}

	private function getUserRelativePath(string $path): string {
		$parts = explode('/', $path, 3);
		if (count($parts) >= 3) {
			return '/' . $parts[2];
		} else {
			return '';
		}
	}

	/**
	 * @param IUser $user
	 * @return ICachedMountInfo[]
	 */
	private function getSystemMountsForUser(IUser $user): array {
		return array_filter($this->userMountCache->getMountsForUser($user), function (ICachedMountInfo $mount) use (
			$user
		) {
			$mountPoint = substr($mount->getMountPoint(), strlen($user->getUID() . '/'));
			return $this->encryptionUtil->isSystemWideMountPoint($mountPoint, $user->getUID());
		});
	}

	/**
	 * Get all files in a folder which are marked as encrypted
	 *
	 * @param Folder $folder
	 * @return \Generator<File>
	 */
	private function getAllEncryptedFiles(Folder $folder) {
		foreach ($folder->getDirectoryListing() as $child) {
			if ($child instanceof Folder) {
				yield from $this->getAllEncryptedFiles($child);
			} else {
				if (substr($child->getName(), -4) !== '.bak' && $child->isEncrypted()) {
					yield $child;
				}
			}
		}
	}

	private function getSystemKeyPath(Node $node): string {
		$path = $this->getUserRelativePath($node->getPath());
		return $this->keyRootDirectory . '/files_encryption/keys/' . $path . '/';
	}

	private function getUserBaseKeyPath(IUser $user): string {
		return $this->keyRootDirectory . '/' . $user->getUID() . '/files_encryption/keys';
	}

	private function getUserKeyPath(IUser $user, Node $node): string {
		$path = $this->getUserRelativePath($node->getPath());
		return $this->getUserBaseKeyPath($user) . '/' . $path . '/';
	}

	private function hasSystemKey(Node $node): bool {
		// this uses View instead of the RootFolder because the keys might not be in the cache
		return $this->rootView->file_exists($this->getSystemKeyPath($node));
	}

	private function hasUserKey(IUser $user, Node $node): bool {
		// this uses View instead of the RootFolder because the keys might not be in the cache
		return $this->rootView->file_exists($this->getUserKeyPath($user, $node));
	}

	/**
	 * Check that the user key stored for a file can decrypt the file
	 *
	 * @param IUser $user
	 * @param File $node
	 * @return bool
	 */
	private function copyUserKeyToSystemAndValidate(IUser $user, File $node): bool {
		$path = trim(substr($node->getPath(), strlen($user->getUID()) + 1), '/');
		$systemKeyPath = $this->keyRootDirectory . '/files_encryption/keys/' . $path . '/';
		$userKeyPath = $this->keyRootDirectory . '/' . $user->getUID() . '/files_encryption/keys/' . $path . '/';

		$this->rootView->copy($userKeyPath, $systemKeyPath);
		if ($this->tryReadFile($node)) {
			// cleanup wrong key location
			$this->rootView->rmdir($userKeyPath);
			return true;
		} else {
			// remove the copied key if we know it's invalid
			$this->rootView->rmdir($systemKeyPath);
			return false;
		}
	}

	private function tryReadFile(File $node): bool {
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
	private function openWithoutDecryption(File $node, string $mode) {
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
	private function isDataEncrypted(File $node): bool {
		$handle = $this->openWithoutDecryption($node, 'r');
		$firstBlock = fread($handle, $this->encryptionUtil->getHeaderSize());
		fclose($handle);

		$header = $this->encryptionUtil->parseRawHeader($firstBlock);
		return isset($header['oc_encryption_module']);
	}

	/**
	 * Attempt to find a key (stored for user) for a file (that needs a system key) even when it's not stored in the expected location
	 *
	 * @param File $node
	 * @return string
	 */
	private function findUserKeyForSystemFile(IUser $user, File $node): ?string {
		$userKeyPath = $this->getUserBaseKeyPath($user);
		$possibleKeys = $this->findKeysByFileName($userKeyPath, $node->getName());
		foreach ($possibleKeys as $possibleKey) {
			if ($this->testSystemKey($user, $possibleKey, $node)) {
				return $possibleKey;
			}
		}
		return null;
	}

	/**
	 * Attempt to find a key for a file even when it's not stored in the expected location
	 *
	 * @param string $basePath
	 * @param string $name
	 * @return \Generator<string>
	 */
	private function findKeysByFileName(string $basePath, string $name) {
		if ($this->rootView->is_dir($basePath . '/' . $name . '/OC_DEFAULT_MODULE')) {
			yield $basePath . '/' . $name;
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
						yield from $this->findKeysByFileName($childPath, $name);
					}
				}
			}
		}
	}

	/**
	 * Test if the provided key is valid as a system key for the file
	 *
	 * @param IUser $user
	 * @param string $key
	 * @param File $node
	 * @return bool
	 */
	private function testSystemKey(IUser $user, string $key, File $node): bool {
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
	 * Decrypt a file with the specified system key and mark the key as not-encrypted
	 *
	 * @param File $node
	 * @param string $key
	 * @return void
	 */
	private function decryptWithSystemKey(File $node, string $key): void {
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

	private function markAsUnEncrypted(Node $node): void {
		$node->getStorage()->getCache()->update($node->getId(), ['encrypted' => 0]);
	}
}
