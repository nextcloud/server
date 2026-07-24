<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
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

class Mkdir extends Command {
	public function __construct(
		private readonly FileUtils $fileUtils,
		private readonly IRootFolder $rootFolder,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		$this
			->setName('files:mkdir')
			->setDescription('Create a new directory')
			->addArgument('path', InputArgument::REQUIRED, 'Target Nextcloud path for the new folder');
	}

	#[\Override]
	public function execute(InputInterface $input, OutputInterface $output): int {
		$path = $input->getArgument('path');
		$node = $this->fileUtils->getNode($path);

		if ($node instanceof Folder) {
			$output->writeln("<info>$path already exists</info>");
			return self::SUCCESS;
		}
		if ($node instanceof File) {
			$output->writeln("<error>$path is a file</error>");
			return self::FAILURE;
		}

		$this->rootFolder->newFolder($path);

		return self::SUCCESS;
	}
}
