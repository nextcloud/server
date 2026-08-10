<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command;

use OC\Core\Command\Info\FileUtils;
use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;
use OCP\Files\File;

#[AsCommand(
	name: 'files:get',
	description: 'Get the contents of a file',
)]
class Get {
	public function __construct(
		private readonly FileUtils $fileUtils,
	) {
	}

	public function __invoke(
		IOutput $output,
		#[Argument(description: 'Source file id or Nextcloud path')]
		string $file,
		#[Argument(name: 'output', description: 'Target local file to output to, defaults to STDOUT')]
		?string $outputFile = null,
	): ExitCode {
		$node = $this->fileUtils->getNode($file);

		if (!$node) {
			$output->writeln("<error>file $file not found</error>");
			return ExitCode::Failure;
		}

		if (!($node instanceof File)) {
			$output->writeln("<error>$file is a directory</error>");
			return ExitCode::Failure;
		}

		$isTTY = stream_isatty(STDOUT);
		if ($outputFile === null && $isTTY && $node->getMimePart() !== 'text') {
			$output->writeln([
				'<error>Warning: Binary output can mess up your terminal</error>',
				"         Use <info>occ files:get $file -</info> to output it to the terminal anyway",
				"         Or <info>occ files:get $file <FILE></info> to save to a file instead"
			]);
			return ExitCode::Failure;
		}
		$source = $node->fopen('r');
		if (!$source) {
			$output->writeln("<error>Failed to open $file for reading</error>");
			return ExitCode::Failure;
		}
		$target = ($outputFile === null || $outputFile === '-') ? STDOUT : fopen($outputFile, 'w');
		if (!$target) {
			$output->writeln("<error>Failed to open $outputFile for reading</error>");
			return ExitCode::Failure;
		}

		stream_copy_to_stream($source, $target);
		return ExitCode::Success;
	}
}
