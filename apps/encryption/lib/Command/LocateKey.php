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

namespace OCA\Encryption\Command;

use OC\Encryption\Util;
use OC\Files\View;
use OCA\Encryption\Repair;
use OCA\Files_Sharing\ISharedStorage;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\IRootFolder;
use OCP\Files\File;
use OCP\Files\NotFoundException;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LocateKey extends Command {
	private IUserManager $userManager;
	private IRootFolder $rootFolder;
	private Repair $repair;
	private View $rootView;

	public function __construct(
		IUserManager $userManager,
		IRootFolder $rootFolder,
		Repair $repair
	) {
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->repair = $repair;
		$this->rootView = new View();

		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();

		$this
			->setName('encryption:locate-key')
			->setDescription('Attempt to find the matching key for a file when the key is lost')
			->addOption('dry-run', null, InputOption::VALUE_NONE, "Don't repair the key once found")
			->addOption('from-all-users', null, InputOption::VALUE_NONE, "Look for keys from every user")
			->addArgument('path', InputArgument::REQUIRED, "Absolute path of the file to locate the key for");
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$dryRun = $input->getOption('dry-run');
		$path = $input->getArgument('path');
		$allUsers = $input->getOption('from-all-users');
		[, $userId, ] = explode('/', $path);
		$user = $this->userManager->get($userId);
		if (!$user) {
			$output->writeln("<error>User $userId not found</error>");
			return 1;
		}
		try {
			$node = $this->rootFolder->get($path);
		} catch (NotFoundException $e) {
			$output->writeln("<error>$path not found</error>");
			return 1;
		}

		if (!$node instanceof File) {
			$output->writeln("<error>$path is a directory, this command can only be ran on files</error>");
			return 1;
		}

		if ($node->getStorage()->instanceOfStorage(ISharedStorage::class)) {
			$output->writeln("<error>$path is a shared file, please run the command for the owner of the file</error>");
			return 1;
		}

		if (!$this->repair->isDataEncrypted($node)) {
			$output->writeln("<error>$path isn't encrypted</error>");
			return 1;
		}

		if ($node->isEncrypted()) {
			// if the file is not marked as encrypted (but the data is actually encrypted as verified above)
			// the `tryRead` check will always pass
			if ($this->repair->tryReadFile($node)) {
				$output->writeln("<info>$path apprears to already have a valid key in the correct location</info>");
				return 1;
			}
		} else {
			$output->writeln("<error>$path isn't marked as encrypted but it's data appears to be encrypted</error>");
			$output->writeln("<error>Repairing this is currently not supported</error>");
			return 1;
		}

		$allKeys = new \AppendIterator();
		$allKeys->append($this->repair->findAllKeysInDirectory($this->repair->getUserKeyRoot($user)));
		$allKeys->append($this->repair->findAllKeysInDirectory($this->repair->getSystemKeyRoot()));
		if ($allUsers) {
			$this->userManager->callForSeenUsers(function(IUser $user) use ($allKeys) {
				$allKeys->append($this->repair->findAllKeysInDirectory($this->repair->getUserKeyRoot($user)));
			});
		}

		$workingKey = $this->testKeys($user, $node, $allKeys);

		if ($workingKey) {
			if ($dryRun) {
				$output->writeln("<info>Found working key at $workingKey</info>");
			} else {
				$target = $this->repair->getKeyPath($user, $node);
				$this->rootView->copy($workingKey, $target);
				$output->writeln("<info>Copied working key from $workingKey to $target</info>");
			}
			return 0;
		} else {
			$output->writeln("<error>No working key found for $path</error>");
			return 1;
		}
	}

	/**
	 * Test all keys until we find one that works for a file
	 *
	 * @param File $node
	 * @param iterable<string> $keys
	 * @return string|null
	 */
	private function testKeys(IUser $user, File $node, iterable $keys): ?string {
		$needsSystemKey = $this->repair->needsSystemKey($node->getPath());
		foreach ($keys as $possibleKey) {
			if ($needsSystemKey) {
				if ($this->repair->testSystemKey($possibleKey, $node)) {
					return $possibleKey;
				}
			} else {
				if ($this->repair->testUserKey($user, $possibleKey, $node)) {
					return $possibleKey;
				}
			}
		}
		return null;
	}
}
