<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
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
	name: 'files:mkdir',
	description: 'Create a new directory',
)]
class Mkdir {
	public function __construct(
		private readonly FileUtils $fileUtils,
		private readonly IRootFolder $rootFolder,
	) {
	}

	public function __invoke(
		IOutput $output,
		#[Argument(description: 'Target Nextcloud path for the new folder')]
		string $path,
	): ExitCode {
		$node = $this->fileUtils->getNode($path);

		if ($node instanceof Folder) {
			$output->writeln("<info>$path already exists</info>");
			return ExitCode::Success;
		}
		if ($node instanceof File) {
			$output->writeln("<error>$path is a file</error>");
			return ExitCode::Failure;
		}

		$this->rootFolder->newFolder($path);

		return ExitCode::Success;
	}
}
