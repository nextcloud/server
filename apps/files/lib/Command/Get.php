<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command;

use OC\Core\Command\Info\FileUtils;
use OCP\Files\File;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Get extends Command {
	public function __construct(
		private FileUtils $fileUtils,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('files:get')
			->setDescription('Get the contents of a file')
			->addArgument('file', InputArgument::REQUIRED, 'Source file id or Nextcloud path')
			->addArgument('output', InputArgument::OPTIONAL, 'Target local file to output to, defaults to STDOUT');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$fileInput = $input->getArgument('file');
		$outputName = $input->getArgument('output');
		$node = $this->fileUtils->getNode($fileInput);

		if (!$node) {
			$output->writeln("<error>file $fileInput not found</error>");
			return self::FAILURE;
		}

		if (!($node instanceof File)) {
			$output->writeln("<error>$fileInput is a directory</error>");
			return self::FAILURE;
		}

		$isTTY = stream_isatty(STDOUT);
		if ($outputName === null && $isTTY && $node->getMimePart() !== 'text') {
			$output->writeln([
				'<error>Warning: Binary output can mess up your terminal</error>',
				"         Use <info>occ files:get $fileInput -</info> to output it to the terminal anyway",
				"         Or <info>occ files:get $fileInput <FILE></info> to save to a file instead"
			]);
			return self::FAILURE;
		}
		$source = $node->fopen('r');
		if (!$source) {
			$output->writeln("<error>Failed to open $fileInput for reading</error>");
			return self::FAILURE;
		}
		$target = ($outputName === null || $outputName === '-') ? STDOUT : fopen($outputName, 'w');
		if (!$target) {
			$output->writeln("<error>Failed to open $outputName for reading</error>");
			return self::FAILURE;
		}

		stream_copy_to_stream($source, $target);
		return self::SUCCESS;
	}
}
