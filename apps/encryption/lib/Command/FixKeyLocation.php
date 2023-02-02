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
use OC\Files\View;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Folder;
use OCP\Files\File;
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

	public function __construct(IUserManager $userManager, IUserMountCache $userMountCache, Util $encryptionUtil, IRootFolder $rootFolder) {
		$this->userManager = $userManager;
		$this->userMountCache = $userMountCache;
		$this->encryptionUtil = $encryptionUtil;
		$this->rootFolder = $rootFolder;
		$this->keyRootDirectory = rtrim($this->encryptionUtil->getKeyStorageRoot(), '/');
		$this->rootView = new View();

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

			$files = $this->getAllFiles($mountRootFolder);
			foreach ($files as $file) {
				if ($this->isKeyStoredForUser($user, $file)) {
					if ($dryRun) {
						$output->writeln("<info>" . $file->getPath() . "</info> needs migration");
					} else {
						$output->write("Migrating key for <info>" . $file->getPath() . "</info> ");
						if ($this->copyKeyAndValidate($user, $file)) {
							$output->writeln("<info>✓</info>");
						} else {
							$output->writeln("<fg=red>❌</>");
							$output->writeln("  Failed to validate key for <error>" . $file->getPath() . "</error>, key will not be migrated");
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
		return array_filter($this->userMountCache->getMountsForUser($user), function(ICachedMountInfo $mount) use ($user) {
			$mountPoint = substr($mount->getMountPoint(), strlen($user->getUID() . '/'));
			return $this->encryptionUtil->isSystemWideMountPoint($mountPoint, $user->getUID());
		});
	}

	/**
	 * @param Folder $folder
	 * @return \Generator<File>
	 */
	private function getAllFiles(Folder $folder) {
		foreach ($folder->getDirectoryListing() as $child) {
			if ($child instanceof Folder) {
				yield from $this->getAllFiles($child);
			} else {
				yield $child;
			}
		}
	}

	/**
	 * Check if the key for a file is stored in the user's keystore and not the system one
	 *
	 * @param IUser $user
	 * @param Node $node
	 * @return bool
	 */
	private function isKeyStoredForUser(IUser $user, Node $node): bool {
		$path = trim(substr($node->getPath(), strlen($user->getUID()) + 1), '/');
		$systemKeyPath = $this->keyRootDirectory . '/files_encryption/keys/' . $path . '/';
		$userKeyPath = $this->keyRootDirectory . '/' . $user->getUID() . '/files_encryption/keys/' . $path . '/';

		// this uses View instead of the RootFolder because the keys might not be in the cache
		$systemKeyExists = $this->rootView->file_exists($systemKeyPath);
		$userKeyExists = $this->rootView->file_exists($userKeyPath);
		return $userKeyExists && !$systemKeyExists;
	}

	/**
	 * Check that the user key stored for a file can decrypt the file
	 *
	 * @param IUser $user
	 * @param File $node
	 * @return bool
	 */
	private function copyKeyAndValidate(IUser $user, File $node): bool {
		$path = trim(substr($node->getPath(), strlen($user->getUID()) + 1), '/');
		$systemKeyPath = $this->keyRootDirectory . '/files_encryption/keys/' . $path . '/';
		$userKeyPath = $this->keyRootDirectory . '/' . $user->getUID() . '/files_encryption/keys/' . $path . '/';

		$this->rootView->copy($userKeyPath, $systemKeyPath);
		try {
			// check that the copied key is valid
			$fh = $node->fopen('r');
			// read a single chunk
			$data = fread($fh, 8192);
			if ($data === false) {
				throw new \Exception("Read failed");
			}

			// cleanup wrong key location
			$this->rootView->rmdir($userKeyPath);
			return true;
		} catch (\Exception $e) {
			// remove the copied key if we know it's invalid
			$this->rootView->rmdir($systemKeyPath);
			return false;
		}
	}
}
