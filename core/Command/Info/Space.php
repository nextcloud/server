<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Info;

use OCP\Files\Folder;
use OCP\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Space extends Command {
	public function __construct(
		private FileUtils $fileUtils,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('info:file:space')
			->setDescription('Summarize space usage of specified folder')
			->addArgument('file', InputArgument::REQUIRED, 'File id or path')
			->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Number of items to display', 25)
			->addOption('all', 'a', InputOption::VALUE_NONE, 'Display all items');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$fileInput = $input->getArgument('file');
		$count = (int)$input->getOption('count');
		$all = $input->getOption('all');
		$node = $this->fileUtils->getNode($fileInput);
		if (!$node) {
			$output->writeln("<error>file $fileInput not found</error>");
			return 1;
		}
		$output->writeln($node->getName() . ': <info>' . Util::humanFileSize($node->getSize()) . '</info>');
		if ($node instanceof Folder) {
			$limits = $all ? [] : array_fill(0, $count - 1, 0);
			$this->fileUtils->outputLargeFilesTree($output, $node, '', $limits, $all);
		}
		return 0;
	}
}
