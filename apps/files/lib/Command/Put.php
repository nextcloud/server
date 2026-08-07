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
use OCP\Files\Folder;
use OCP\Files\IRootFolder;

#[AsCommand(
	name: 'files:put',
	description: 'Write contents of a file',
)]
class Put {
	public function __construct(
		private readonly FileUtils $fileUtils,
		private readonly IRootFolder $rootFolder,
	) {
	}

	public function __invoke(
		IOutput $output,
		#[Argument(description: 'Source local path, use - to read from STDIN')] string $input,
		#[Argument(description: 'Target Nextcloud file path to write to or fileid of existing file')] string $file,
	): ExitCode {
		$node = $this->fileUtils->getNode($file);

		if ($node instanceof Folder) {
			$output->writeln("<error>$file is a folder</error>");
			return ExitCode::Failure;
		}
		if (!$node && is_numeric($file)) {
			$output->writeln("<error>$file not found</error>");
			return ExitCode::Failure;
		}

		$source = ($input === '-') ? STDIN : fopen($input, 'r');
		if (!$source) {
			$output->writeln("<error>Failed to open $input</error>");
			return ExitCode::Failure;
		}
		if ($node instanceof File) {
			$target = $node->fopen('w');
			if (!$target) {
				$output->writeln("<error>Failed to open $file</error>");
				return ExitCode::Failure;
			}
			stream_copy_to_stream($source, $target);
		} else {
			$parentPath = dirname($file);
			if (!$this->rootFolder->nodeExists($parentPath)) {
				$this->rootFolder->newFolder($parentPath);
			}

			$this->rootFolder->newFile($file, $source);
		}
		return ExitCode::Success;
	}
}
