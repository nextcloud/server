<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command;

use OC\Core\Command\Info\FileUtils;
use OCP\Files\File;
use OCP\Files\Folder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Move extends Command {
	private FileUtils $fileUtils;

	public function __construct(FileUtils $fileUtils) {
		$this->fileUtils = $fileUtils;
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('files:move')
			->setDescription('Move a file or folder')
			->addArgument('source', InputArgument::REQUIRED, 'Source file id or path')
			->addArgument('target', InputArgument::REQUIRED, 'Target path')
			->addOption('force', 'f', InputOption::VALUE_NONE, "Don't ask for configuration and don't output any warnings");
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$sourceInput = $input->getArgument('source');
		$targetInput = $input->getArgument('target');
		$force = $input->getOption('force');

		$node = $this->fileUtils->getNode($sourceInput);
		$targetNode = $this->fileUtils->getNode($targetInput);

		if (!$node) {
			$output->writeln("<error>file $sourceInput not found</error>");
			return 1;
		}

		$targetParentPath = dirname(rtrim($targetInput, '/'));
		$targetParent = $this->fileUtils->getNode($targetParentPath);
		if (!$targetParent) {
			$output->writeln("<error>Target parent path $targetParentPath doesn't exist</error>");
			return 1;
		}

		$wouldRequireDelete = false;

		if ($targetNode) {
			if (!$targetNode->isUpdateable()) {
				$output->writeln("<error>$targetInput already exists and isn't writable</error>");
				return 1;
			}

			if ($node instanceof Folder && $targetNode instanceof File) {
				$output->writeln("Warning: <info>$sourceInput</info> is a folder, but <info>$targetInput</info> is a file");
				$wouldRequireDelete = true;
			}

			if ($node instanceof File && $targetNode instanceof Folder) {
				$output->writeln("Warning: <info>$sourceInput</info> is a file, but <info>$targetInput</info> is a folder");
				$wouldRequireDelete = true;
			}

			if ($wouldRequireDelete && $targetNode->getInternalPath() === '') {
				$output->writeln("<error>Mount root can't be overwritten with a different type</error>");
				return 1;
			}

			if ($wouldRequireDelete && !$targetNode->isDeletable()) {
				$output->writeln("<error>$targetInput can't be deleted to be replaced with $sourceInput</error>");
				return 1;
			}

			if (!$force) {
				/** @var QuestionHelper $helper */
				$helper = $this->getHelper('question');

				$question = new ConfirmationQuestion('<info>' . $targetInput . '</info> already exists, overwrite? [y/N] ', false);
				if (!$helper->ask($input, $output, $question)) {
					return 1;
				}
			}
		}

		if ($wouldRequireDelete && $targetNode) {
			$targetNode->delete();
		}

		$node->move($targetInput);

		return 0;
	}

}
