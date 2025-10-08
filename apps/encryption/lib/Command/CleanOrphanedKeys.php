<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Encryption\Command;

use OC\Encryption\Util;
use OC\Files\View;
use OCP\IConfig;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class CleanOrphanedKeys extends Command {
	private View $rootView;

	public function __construct(
		protected Util $util,
		protected IConfig $config,
		protected QuestionHelper $questionHelper,
		private IUserManager $userManager,
		private Util $encryptionUtil,
	) {
		parent::__construct();

		$this->rootView = new View();
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

		foreach ($this->userManager->getBackends() as $backend) {
			$limit = 500;
			$offset = 0;

			do {
				$users = $backend->getUsers('', $limit, $offset);
				foreach ($users as $user) {
					$progress->setMessage('Scanning all keys for: ' . $user);
					$progress->advance();
					$this->setupUserFS($user);
					$root = $this->encryptionUtil->getKeyStorageRoot() . '/' . $user . '/files_encryption/keys';
					$userOrphanedKeys = $this->scanFolder($output, $root, $user);
					$orphanedKeys = array_merge($orphanedKeys, $userOrphanedKeys);
				}
				$offset += $limit;
			} while (count($users) >= $limit);
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

	private function scanFolder(OutputInterface $output, string $folder, string $user) : array {
		$orphanedKeys = [];
		foreach ($this->rootView->getDirectoryContent($folder) as $item) {
			$path = $folder . '/' . $item['name'];
			if ($this->stopCondition($path)) {
				$filePath = str_replace('files_encryption/keys/', '', $path);
				if (!$this->rootView->getFileInfo($filePath, true, true)) {
					$orphanedKeys[] = $path;
				}
			} else {
				$orphanedKeys = array_merge($orphanedKeys, $this->scanFolder($output, $path, $user));
			}
		}
		return $orphanedKeys;
	}

	private function stopCondition(string $path) : bool {
		if ($this->rootView->is_dir($path)) {
			$content = $this->rootView->getDirectoryContent($path);

			if (count($content) === 1 && $content[0]['name'] === 'OC_DEFAULT_MODULE') {
				$path = $path . '/' . $content[0]['name'];
				if ($this->rootView->is_dir($path)) {
					$content = $this->rootView->getDirectoryContent($path);
					$path = $path . '/' . $content[0]['name'];
					if (count($content) === 1 && $this->rootView->is_file($path)) {
						return strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'sharekey' ;
					}
				}
			}
			return false;
		}
	}
	private function deleteAll(array $keys, OutputInterface $output) {
		foreach ($keys as $key) {
			if ($this->rootView->unlink($key)) {
				$output->writeln('Key deleted: ' . $key);
			} else {
				$output->writeln('Failed to delete: ' . $key);
			}
		}
	}

	private function deleteSpecific(InputInterface $input, OutputInterface $output, array $orphanedKeys) {
		$question = new Question('Please enter path for key to delete: ');
		$path = $this->questionHelper->ask($input, $output, $question);
		if (!in_array(trim($path), $orphanedKeys)) {
			$output->writeln('Wrong key path');
		} else {
			$this->rootView->unlink(trim($path));
			$orphanedKeys = array_filter($orphanedKeys, function ($k) use ($path) {
				return $k !== trim($path);
			});
		}
		$output->writeln('Remaining orphaned keys: ');
		foreach ($orphanedKeys as $keyPath) {
			$output->writeln($keyPath);
		}
		if (count($orphanedKeys) == 0) {
			return;
		}
		$question = new ConfirmationQuestion('Do you want to delete more orphaned keys? (y/n) ', false);
		if ($this->questionHelper->ask($input, $output, $question)) {
			$this->deleteSpecific($input, $output, $orphanedKeys);
		}

	}

	/**
	 * setup user file system
	 */
	protected function setupUserFS(string $uid): void {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($uid);
	}
}
