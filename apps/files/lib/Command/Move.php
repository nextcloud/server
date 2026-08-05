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
use OCP\Console\Attribute\Option;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;
use OCP\Console\IQuestionHelper;
use OCP\Files\File;
use OCP\Files\Folder;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
	name: 'files:move',
	description: 'Move a file or a folder',
)]
class Move {
	public function __construct(
		private readonly FileUtils $fileUtils,
	) {
	}

	public function __invoke(
		IOutput $output,
		IQuestionHelper $questionHelper,
		#[Argument(description: 'Source file id or path')] string $source,
		#[Argument(description: 'Target path')] string $target,
		#[Option(
			description: "Don't ask for configuration and don't output any warnings",
			shortcut: 'f',
		)] bool $force = false,
	): ExitCode {
		$node = $this->fileUtils->getNode($source);
		$targetNode = $this->fileUtils->getNode($target);

		if (!$node) {
			$output->writeln("<error>file $source not found</error>");
			return ExitCode::Failure;
		}

		$targetParentPath = dirname(rtrim($target, '/'));
		$targetParent = $this->fileUtils->getNode($targetParentPath);
		if (!$targetParent) {
			$output->writeln("<error>Target parent path $targetParentPath doesn't exist</error>");
			return ExitCode::Failure;
		}

		$wouldRequireDelete = false;

		if ($targetNode) {
			if (!$targetNode->isUpdateable()) {
				$output->writeln("<error>$target already exists and isn't writable</error>");
				return ExitCode::Failure;
			}

			if ($node instanceof Folder && $targetNode instanceof File) {
				$output->writeln("Warning: <info>$source</info> is a folder, but <info>$target</info> is a file");
				$wouldRequireDelete = true;
			}

			if ($node instanceof File && $targetNode instanceof Folder) {
				$output->writeln("Warning: <info>$source</info> is a file, but <info>$target</info> is a folder");
				$wouldRequireDelete = true;
			}

			if ($wouldRequireDelete && $targetNode->getInternalPath() === '') {
				$output->writeln("<error>Mount root can't be overwritten with a different type</error>");
				return ExitCode::Failure;
			}

			if ($wouldRequireDelete && !$targetNode->isDeletable()) {
				$output->writeln("<error>$target can't be deleted to be replaced with $source</error>");
				return ExitCode::Failure;
			}

			if (!$force) {
				$question = new ConfirmationQuestion('<info>' . $target . '</info> already exists, overwrite? [y/N] ', false);
				if (!$questionHelper->ask($question)) {
					return ExitCode::Failure;
				}
			}
		}

		if ($wouldRequireDelete && $targetNode) {
			$targetNode->delete();
		}

		$node->move($target);

		return ExitCode::Success;
	}
}
