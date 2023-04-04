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

use OC\Encryption\Util;
use OC\Files\Storage\Wrapper\Encryption;
use OC\Files\View;
use OCA\Encryption\Repair;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Folder;
use OCP\Files\File;
use OCP\Files\IRootFolder;
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
	private IRootFolder $rootFolder;
	private View $rootView;
	private Repair $repair;

	public function __construct(
		IUserManager $userManager,
		IUserMountCache $userMountCache,
		IRootFolder $rootFolder,
		Repair $repair
	) {
		$this->userManager = $userManager;
		$this->userMountCache = $userMountCache;
		$this->rootFolder = $rootFolder;
		$this->rootView = new View();
		$this->repair = $repair;

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
				$hasSystemKey = $this->repair->hasSystemKey($file);
				$hasUserKey = $this->repair->hasUserKey($user, $file);
				if (!$hasSystemKey && $hasUserKey) {
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
				} elseif (!$hasSystemKey && !$hasUserKey) {
					// no matching key, probably from a broken cross-storage move

					$shouldBeEncrypted = $file->getStorage()->instanceOfStorage(Encryption::class);
					$isActuallyEncrypted = $this->repair->isDataEncrypted($file);
					if ($isActuallyEncrypted) {
						if ($dryRun) {
							if ($shouldBeEncrypted) {
								$output->write("<info>" . $file->getPath() . "</info> needs migration");
							} else {
								$output->write("<info>" . $file->getPath() . "</info> needs decryption");
							}
							$foundKey = $this->findUserKeyForSystemFileByName($user, $file);
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
							$foundKey = $this->findUserKeyForSystemFileByName($user, $file);
							if ($foundKey) {
								if ($shouldBeEncrypted) {
									$systemKeyPath = $this->repair->getSystemKeyPath($file);
									$this->rootView->copy($foundKey, $systemKeyPath);
									$output->writeln("  Migrated key from <info>" . $foundKey . "</info>");
								} else {
									$this->repair->decryptWithSystemKey($file, $foundKey);
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
							$this->repair->markAsUnEncrypted($file);
							$output->writeln("<info>" . $file->getPath() . " marked as not encrypted</info>");
						}
					}
				}
			}
		}

		return 0;
	}

	/**
	 * @param IUser $user
	 * @return ICachedMountInfo[]
	 */
	private function getSystemMountsForUser(IUser $user): array {
		return array_filter($this->userMountCache->getMountsForUser($user), function (ICachedMountInfo $mount){
			return $this->repair->needsSystemKey($mount->getMountPoint());
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

	/**
	 * Check that the user key stored for a file can decrypt the file
	 *
	 * @param IUser $user
	 * @param File $node
	 * @return bool
	 */
	private function copyUserKeyToSystemAndValidate(IUser $user, File $node): bool {
		$path = trim(substr($node->getPath(), strlen($user->getUID()) + 1), '/');
		$systemKeyPath = $this->repair->getSystemKeyRoot() . '/' . $path . '/';
		$userKeyPath = $this->repair->getUserKeyRoot($user) . '/' . $path . '/';

		$this->rootView->copy($userKeyPath, $systemKeyPath);
		if ($this->repair->tryReadFile($node)) {
			// cleanup wrong key location
			$this->rootView->rmdir($userKeyPath);
			return true;
		} else {
			// remove the copied key if we know it's invalid
			$this->rootView->rmdir($systemKeyPath);
			return false;
		}
	}

	/**
	 * Attempt to find a key (stored for user) for a file (that needs a system key) even when it's not stored in the expected location
	 *
	 * @param File $node
	 * @return string
	 */
	public function findUserKeyForSystemFileByName(IUser $user, File $node): ?string {
		$userKeyPath = $this->repair->getUserKeyRoot($user);
		$possibleKeys = $this->findKeysByFileName($userKeyPath, $node->getName());
		foreach ($possibleKeys as $possibleKey) {
			if ($this->repair->testSystemKey($possibleKey, $node)) {
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
	 * @return \Iterator<mixed, string>
	 */
	public function findKeysByFileName(string $basePath, string $name) {
		$allKeys = $this->repair->findAllKeysInDirectory($basePath);
		return new \CallbackFilterIterator($allKeys, function($path) use ($name) {
			$parts = explode('/', $path);
			return array_pop($parts) === $name;
		});
	}
}
