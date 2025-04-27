<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command;

use OC\Core\Command\Info\FileUtils;
use OCA\Files_Sharing\SharedStorage;
use OCP\Files\Folder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Delete extends Command {
	public function __construct(
		private FileUtils $fileUtils,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('files:delete')
			->setDescription('Delete a file or folder')
			->addArgument('file', InputArgument::REQUIRED, 'File id or path')
			->addOption('force', 'f', InputOption::VALUE_NONE, "Don't ask for configuration and don't output any warnings");
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$fileInput = $input->getArgument('file');
		$inputIsId = is_numeric($fileInput);
		$force = $input->getOption('force');
		$node = $this->fileUtils->getNode($fileInput);

		if (!$node) {
			$output->writeln("<error>file $fileInput not found</error>");
			return self::FAILURE;
		}

		$deleteConfirmed = $force;
		if (!$deleteConfirmed) {
			/** @var QuestionHelper $helper */
			$helper = $this->getHelper('question');
			$storage = $node->getStorage();
			if (!$inputIsId && $storage->instanceOfStorage(SharedStorage::class) && $node->getInternalPath() === '') {
				/** @var SharedStorage $storage */
				[,$user] = explode('/', $fileInput, 3);
				$question = new ConfirmationQuestion("<info>$fileInput</info> in a shared file, do you want to unshare the file from <info>$user</info> instead of deleting the source file? [Y/n] ", true);
				if ($helper->ask($input, $output, $question)) {
					$storage->unshareStorage();
					return self::SUCCESS;
				} else {
					$node = $storage->getShare()->getNode();
					$output->writeln('');
				}
			}

			$filesByUsers = $this->fileUtils->getFilesByUser($node);
			if (count($filesByUsers) > 1) {
				$output->writeln('Warning: the provided file is accessible by more than one user');
				$output->writeln('  all of the following users will lose access to the file when deleted:');
				$output->writeln('');
				foreach ($filesByUsers as $user => $filesByUser) {
					$output->writeln($user . ':');
					foreach ($filesByUser as $file) {
						$output->writeln('  - ' . $file->getPath());
					}
				}
				$output->writeln('');
			}

			if ($node instanceof Folder) {
				$maybeContents = " and all it's contents";
			} else {
				$maybeContents = '';
			}
			$question = new ConfirmationQuestion('Delete ' . $node->getPath() . $maybeContents . '? [y/N] ', false);
			$deleteConfirmed = $helper->ask($input, $output, $question);
		}

		if ($deleteConfirmed) {
			if ($node->isDeletable()) {
				$node->delete();
			} else {
				$output->writeln('<error>File cannot be deleted, insufficient permissions.</error>');
			}
		}

		return self::SUCCESS;
	}
}
