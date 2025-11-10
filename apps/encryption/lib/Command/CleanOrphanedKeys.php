<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Encryption\Command;

use OC\Encryption\Util;
use OC\Files\SetupManager;
use OCA\Encryption\Crypto\Encryption;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class CleanOrphanedKeys extends Command {

	public function __construct(
		protected IConfig $config,
		protected QuestionHelper $questionHelper,
		private IUserManager $userManager,
		private Util $encryptionUtil,
		private SetupManager $setupManager,
		private IRootFolder $rootFolder,
		private LoggerInterface $logger,
	) {
		parent::__construct();

	}

	protected function configure(): void {
		$this
			->setName('encryption:clean-orphaned-keys')
			->setDescription('Scan the keys storage for orphaned keys and remove them');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$orphanedKeys = [];
		$headline = 'Scanning all keys for file parity';
		$output->writeln($headline);
		$output->writeln(str_pad('', strlen($headline), '='));
		$output->writeln("\n");
		$progress = new ProgressBar($output);
		$progress->setFormat(" %message% \n [%bar%]");

		foreach ($this->userManager->getSeenUsers() as $user) {
			$uid = $user->getUID();
			$progress->setMessage('Scanning all keys for: ' . $uid);
			$progress->advance();
			$this->setupUserFileSystem($user);
			$root = $this->encryptionUtil->getKeyStorageRoot() . '/' . $uid . '/files_encryption/keys';
			$userOrphanedKeys = $this->scanFolder($output, $root, $uid);
			$orphanedKeys = array_merge($orphanedKeys, $userOrphanedKeys);
		}
		$progress->setMessage('Scanned orphaned keys for all users');
		$progress->finish();
		$output->writeln("\n");
		foreach ($orphanedKeys as $keyPath) {
			$output->writeln('Orphaned key found: ' . $keyPath);
		}
		if (count($orphanedKeys) == 0) {
			return self::SUCCESS;
		}
		$question = new ConfirmationQuestion('Do you want to delete all orphaned keys? (y/n) ', false);
		if ($this->questionHelper->ask($input, $output, $question)) {
			$this->deleteAll($orphanedKeys, $output);
		} else {

			$question = new ConfirmationQuestion('Do you want to delete specific keys? (y/n) ', false);
			if ($this->questionHelper->ask($input, $output, $question)) {
				$this->deleteSpecific($input, $output, $orphanedKeys);
			}
		}

		return self::SUCCESS;
	}

	private function scanFolder(OutputInterface $output, string $folderPath, string $user) : array {
		$orphanedKeys = [];
		try {
			$folder = $this->rootFolder->get($folderPath);
		} catch (NotFoundException $e) {
			// Happens when user doesn't have encrypted files
			$this->logger->error('Error when accessing folder ' . $folderPath . ' for user ' . $user, ['exception' => $e]);
			return [];
		}

		if (!($folder instanceof Folder)) {
			$this->logger->error('Invalid folder');
			return [];
		}

		foreach ($folder->getDirectoryListing() as $item) {
			$path = $folderPath . '/' . $item->getName();
			$stopValue = $this->stopCondition($path);
			if ($stopValue === null) {
				$this->logger->error('Reached unexpected state when scanning user\'s filesystem for orphaned encryption keys' . $path);
			} elseif ($stopValue) {
				$filePath = str_replace('files_encryption/keys/', '', $path);
				try {
					$this->rootFolder->get($filePath);
				} catch (NotFoundException $e) {
					// We found an orphaned key
					$orphanedKeys[] = $path;
					continue;
				}
			} else {
				$orphanedKeys = array_merge($orphanedKeys, $this->scanFolder($output, $path, $user));
			}
		}
		return $orphanedKeys;
	}
	/**
	 * Checks the stop considition for the recursion
	 * following the logic that keys are stored in files_encryption/keys/<user>/<path>/<fileName>/OC_DEFAULT_MODULE/<key>.sharekey
	 * @param string $path path of the current folder
	 * @return bool|null true if we should stop and found a key, false if we should continue, null if we shouldn't end up here
	 */
	private function stopCondition(string $path) : ?bool {
		$folder = $this->rootFolder->get($path);
		if ($folder instanceof Folder) {
			$content = $folder->getDirectoryListing();
			$subfolder = $content[0];
			if (count($content) === 1 && $subfolder->getName() === Encryption::ID) {
				if ($subfolder instanceof Folder) {
					$content = $subfolder->getDirectoryListing();
					if (count($content) === 1 && $content[0] instanceof File) {
						return strtolower($content[0]->getExtension()) === 'sharekey' ;
					}
				}
			}
			return false;
		}
		// We shouldn't end up here, because we return true when reaching the folder named after the file containing OC_DEFAULT_MODULE
		return null;
	}
	private function deleteAll(array $keys, OutputInterface $output) {
		foreach ($keys as $key) {
			$file = $this->rootFolder->get($key);
			try {
				$file->delete();
				$output->writeln('Key deleted: ' . $key);
			} catch (\Exception $e) {
				$output->writeln('Failed to delete  ' . $key);
				$this->logger->error('Error when deleting orphaned key ' . $key . '. ' . $e->getMessage());
			}
		}
	}

	private function deleteSpecific(InputInterface $input, OutputInterface $output, array $orphanedKeys) {
		$question = new Question('Please enter path for key to delete: ');
		$path = $this->questionHelper->ask($input, $output, $question);
		if (!in_array(trim($path), $orphanedKeys)) {
			$output->writeln('Wrong key path');
		} else {
			try {
				$this->rootFolder->get(trim($path))->delete();
				$output->writeln('Key deleted: ' . $path);
			} catch (\Exception $e) {
				$output->writeln('Failed to delete  ' . $path);
				$this->logger->error('Error when deleting orphaned key ' . $path . '. ' . $e->getMessage());
			}
			$orphanedKeys = array_filter($orphanedKeys, function ($k) use ($path) {
				return $k !== trim($path);
			});
		}
		if (count($orphanedKeys) == 0) {
			return;
		}
		$output->writeln('Remaining orphaned keys: ');
		foreach ($orphanedKeys as $keyPath) {
			$output->writeln($keyPath);
		}
		$question = new ConfirmationQuestion('Do you want to delete more orphaned keys? (y/n) ', false);
		if ($this->questionHelper->ask($input, $output, $question)) {
			$this->deleteSpecific($input, $output, $orphanedKeys);
		}

	}

	/**
	 * setup user file system
	 */
	protected function setupUserFileSystem(IUser $user): void {
		$this->setupManager->tearDown();
		$this->setupManager->setupForUser($user);
	}
}
