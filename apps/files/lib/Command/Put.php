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
use OCP\Files\IRootFolder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Put extends Command {
	public function __construct(
		private FileUtils $fileUtils,
		private IRootFolder $rootFolder,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('files:put')
			->setDescription('Write contents of a file')
			->addArgument('input', InputArgument::REQUIRED, 'Source local path, use - to read from STDIN')
			->addArgument('file', InputArgument::REQUIRED, 'Target Nextcloud file path to write to or fileid of existing file');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$fileOutput = $input->getArgument('file');
		$inputName = $input->getArgument('input');
		$node = $this->fileUtils->getNode($fileOutput);

		if ($node instanceof Folder) {
			$output->writeln("<error>$fileOutput is a folder</error>");
			return self::FAILURE;
		}
		if (!$node and is_numeric($fileOutput)) {
			$output->writeln("<error>$fileOutput not found</error>");
			return self::FAILURE;
		}

		$source = ($inputName === null || $inputName === '-') ? STDIN : fopen($inputName, 'r');
		if (!$source) {
			$output->writeln("<error>Failed to open $inputName</error>");
			return self::FAILURE;
		}
		if ($node instanceof File) {
			$target = $node->fopen('w');
			if (!$target) {
				$output->writeln("<error>Failed to open $fileOutput</error>");
				return self::FAILURE;
			}
			stream_copy_to_stream($source, $target);
		} else {
			$this->rootFolder->newFile($fileOutput, $source);
		}
		return self::SUCCESS;
	}
}
