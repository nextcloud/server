<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Core\Command\Encryption;

use OC\Encryption\Keys\Storage;
use OC\Encryption\Util;
use OC\Files\Filesystem;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\ISetupManager;
use OCP\Files\NotFoundException;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ChangeKeyStorageRoot extends Command {
	public function __construct(
		private readonly IUserManager $userManager,
		private readonly Util $util,
		private readonly QuestionHelper $questionHelper,
		private readonly ISetupManager $setupManager,
		private readonly IRootFolder $rootFolder,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		parent::configure();
		$this
			->setName('encryption:change-key-storage-root')
			->setDescription('Change key storage root')
			->addArgument(
				'newRoot',
				InputArgument::OPTIONAL,
				'new root of the key storage relative to the data folder'
			);
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$oldRoot = $this->util->getKeyStorageRoot();
		$newRoot = $input->getArgument('newRoot');

		if ($newRoot === null) {
			$question = new ConfirmationQuestion('No storage root given, do you want to reset the key storage root to the default location? (y/n) ', false);
			if (!$this->questionHelper->ask($input, $output, $question)) {
				return 1;
			}
			$newRoot = '';
		}

		$oldRootDescription = $oldRoot !== '' ? $oldRoot : 'default storage location';
		$newRootDescription = $newRoot !== '' ? $newRoot : 'default storage location';
		$output->writeln("Change key storage root from <info>$oldRootDescription</info> to <info>$newRootDescription</info>");
		$success = $this->moveAllKeys($oldRoot, $newRoot, $output);
		if ($success) {
			$this->util->setKeyStorageRoot($newRoot);
			$output->writeln('');
			$output->writeln("Key storage root successfully changed to <info>$newRootDescription</info>");
			return 0;
		}
		return 1;
	}

	/**
	 * Move keys to new key storage root.
	 *
	 * @throws \Exception
	 */
	protected function moveAllKeys(string $oldRoot, string $newRoot, OutputInterface $output): bool {
		$output->writeln('Start to move keys:');

		try {
			$oldRootFolder = $this->rootFolder->get($oldRoot);
		} catch (NotFoundException) {
			$output->writeln('No old keys found: Nothing needs to be moved');
			return false;
		}

		if (!$oldRootFolder instanceof Folder) {
			$output->writeln('Old root is not a folder');
			return false;
		}

		$this->prepareNewRoot($newRoot);
		$this->moveSystemKeys($oldRootFolder, $newRoot);
		$this->moveUserKeys($oldRootFolder, $newRoot, $output);

		return true;
	}

	/**
	 * prepare new key storage
	 *
	 * @throws \Exception
	 */
	protected function prepareNewRoot(string $newRoot): void {
		if (!$this->rootFolder->nodeExists($newRoot)) {
			throw new \Exception("New root folder doesn't exist. Please create the folder or check the permissions and try again.");
		}

		/** @var File $node */
		$node = $this->rootFolder->get($newRoot . '/' . Storage::KEY_STORAGE_MARKER);
		try {
			$node->putContent('Nextcloud will detect this folder as key storage root only if this file exists');
		} catch (\Exception $e) {
			throw new \Exception("Can't access the new root folder. Please check the permissions and make sure that the folder is in your data folder", previous: $e);
		}
	}

	/**
	 * Move system key folder
	 */
	protected function moveSystemKeys(Folder $oldRoot, string $newRoot): void {
		try {
			$fileEncryptionFolder = $oldRoot->get('files_encryption');
		} catch (NotFoundException) {
			return;
		}

		if (!$this->targetExists($newRoot . '/files_encryption')) {
			$fileEncryptionFolder->move($newRoot . '/files_encryption');
		}
	}

	/**
	 * setup file system for the given user
	 */
	protected function setupUserFS(IUser $user): void {
		$this->setupManager->tearDown();
		$this->setupManager->setupForUser($user);
	}

	/**
	 * iterate over each user and move the keys to the new storage
	 */
	protected function moveUserKeys(Folder $oldRootFolder, string $newRoot, OutputInterface $output): void {
		$progress = new ProgressBar($output);
		$progress->start();

		$this->userManager->callForAllUsers(function (IUser $user) use ($progress, $oldRootFolder, $newRoot, $output) {
			$progress->advance();
			$this->setupUserFS($user);
			$this->moveUserEncryptionFolder($user, $oldRootFolder, $newRoot);
		});
		$progress->finish();
	}

	/**
	 * move user encryption folder to new root folder
	 *
	 * @throws \Exception
	 */
	protected function moveUserEncryptionFolder(IUser $user, Folder $oldRootFolder, string $newRoot): void {
		try {
			$fileEncryptionFolder = $oldRootFolder->get($user->getUID() . '/files_encryption');
		} catch (NotFoundException) {
			return;
		}

		$target = $newRoot . '/' . $user->getUID() . '/files_encryption';
		if (!$this->targetExists($target)) {
			$this->prepareParentFolder($newRoot . '/' . $user->getUID());
			$fileEncryptionFolder->move($target);
		}
	}

	/**
	 * Make preparations to filesystem for saving a key file
	 *
	 * @param string $path relative to data/
	 */
	protected function prepareParentFolder(string $path): void {
		$path = Filesystem::normalizePath($path);
		// If the file resides within a subdirectory, create it
		if ($this->rootFolder->nodeExists($path) === false) {
			$sub_dirs = explode('/', ltrim($path, '/'));
			$dir = '';
			foreach ($sub_dirs as $sub_dir) {
				$dir .= '/' . $sub_dir;
				if ($this->rootFolder->nodeExists($dir) === false) {
					$this->rootFolder->newFolder($dir);
				}
			}
		}
	}

	/**
	 * Check if target already exists
	 *
	 * @throws \Exception
	 */
	protected function targetExists(string $path): bool {
		try {
			$this->rootFolder->get($path);
			throw new \Exception("new folder '$path' already exists");
		} catch (NotFoundException) {
			return false;
		}
	}
}
