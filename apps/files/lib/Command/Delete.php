<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command;

use OC\Core\Command\Info\FileUtils;
use OCA\Files_Sharing\SharedStorage;
use OCA\Files_Trashbin\Trash\ITrashManager;
use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\Attribute\Option;
use OCP\Console\ExitCode;
use OCP\Console\IInput;
use OCP\Console\IOutput;
use OCP\Files\Folder;

#[AsCommand(
	name: 'files:delete',
	description: 'Delete a file or folder',
)]
class Delete {
	public function __construct(
		private readonly FileUtils $fileUtils,
		private readonly ?ITrashManager $trashManager = null,
	) {
	}

	public function __invoke(
		IOutput $output,
		IInput $input,
		#[Argument(description: 'File id or path')]
		string $file,
		#[Option(
			description: "Don't ask for configuration and don't output any warnings",
			shortcut: 'f',
		)]
		bool $force = false,
		#[Option(name: 'skip-trash', description: 'Bypass the trashbin when deleting the file or folder')]
		bool $skipTrash = false,
	): ExitCode {
		$inputIsId = is_numeric($file);
		$node = $this->fileUtils->getNode($file);

		if (!$node) {
			$output->writeln("<error>file $file not found</error>");
			return ExitCode::Failure;
		}

		$deleteConfirmed = $force;
		if (!$deleteConfirmed) {
			$storage = $node->getStorage();
			if (!$inputIsId && $storage->instanceOfStorage(SharedStorage::class) && $node->getInternalPath() === '') {
				/** @var SharedStorage $storage */
				[,$user] = explode('/', $file, 3);
				if ($input->confirm("<info>$file</info> in a shared file, do you want to unshare the file from <info>$user</info> instead of deleting the source file? [Y/n] ", true)) {
					$storage->unshareStorage();
					return ExitCode::Success;
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
					foreach ($filesByUser as $userFile) {
						$output->writeln('  - ' . $userFile->getPath());
					}
				}
				$output->writeln('');
			}

			if ($node instanceof Folder) {
				$maybeContents = " and all it's contents";
			} else {
				$maybeContents = '';
			}
			$deleteConfirmed = $input->confirm('Delete ' . $node->getPath() . $maybeContents . '? [y/N] ', false);
		}

		if ($deleteConfirmed) {
			if ($node->isDeletable()) {
				if ($skipTrash && $this->trashManager) {
					$this->trashManager->pauseTrash();
				}

				$node->delete();
			} else {
				$output->writeln('<error>File cannot be deleted, insufficient permissions.</error>');
			}
		}

		return ExitCode::Success;
	}
}
