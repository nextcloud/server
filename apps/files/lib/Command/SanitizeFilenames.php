<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command;

use Exception;
use OC\Files\FilenameValidator;
use OCA\Files\Service\SettingsService;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\Attribute\Option;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;
use OCP\Console\Verbosity;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Lock\LockedException;

#[AsCommand(
	name: 'files:sanitize-filenames',
	description: 'Renames files to match naming constraints',
)]
class SanitizeFilenames {

	private IOutput $output;
	private ?string $charReplacement;
	private bool $dryRun;
	private bool $errorsOrSkipped = false;

	public function __construct(
		private readonly IUserManager $userManager,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $session,
		private readonly IFactory $l10nFactory,
		private readonly FilenameValidator $filenameValidator,
		private readonly SettingsService $service,
		private readonly IAppConfig $appConfig,
	) {
	}

	public function __invoke(
		IOutput $output,
		#[Argument(name: 'user_id', description: 'will only rename files the given user(s) have access to')]
		array $userIds = [],
		#[Option(name: 'dry-run', description: 'Do not actually rename any files but just check filenames.')]
		bool $dryRun = false,
		#[Option(name: 'char-replacement', description: 'Replacement for invalid character (by default space, underscore or dash is used)', shortcut: 'c')]
		?string $charReplacement = null,
	): ExitCode {
		$this->charReplacement = $charReplacement;
		// check if replacement is needed
		$c = $this->filenameValidator->getForbiddenCharacters();
		if (count($c) > 0) {
			try {
				$this->filenameValidator->sanitizeFilename($c[0], $this->charReplacement);
			} catch (\InvalidArgumentException) {
				if ($this->charReplacement === null) {
					$output->writeln('<error>Character replacement required</error>');
				} else {
					$output->writeln('<error>Invalid character replacement given</error>');
				}
				return ExitCode::Failure;
			}
		}

		$this->dryRun = $dryRun;
		if ($this->dryRun) {
			$output->writeln('<info>Dry run is enabled, no actual renaming will be applied.</>');
		}

		$this->output = $output;
		if (!empty($userIds)) {
			foreach ($userIds as $userId) {
				$user = $this->userManager->get($userId);
				if ($user === null) {
					$output->writeln("<error>User '$userId' does not exist - skipping</>");
					continue;
				}
				$this->sanitizeUserFiles($user);
			}
		} else {
			$this->userManager->callForSeenUsers($this->sanitizeUserFiles(...));
			if ($this->service->hasFilesWindowsSupport() && $this->appConfig->getAppValueInt('sanitize_filenames_status') === 0) {
				// we are done - if this is for sanitizing all users for windows filename support then set this UI flag
				$this->appConfig->setAppValueInt('sanitize_filenames_status', SettingsService::STATUS_WCF_DONE);
			}
		}
		return ExitCode::Success;
	}

	private function sanitizeUserFiles(IUser $user): void {
		// Set an active user so that event listeners can correctly work (e.g. files versions)
		$this->session->setVolatileActiveUser($user);

		$this->output->writeln('<info>Analyzing files of ' . $user->getUID() . '</>');

		$folder = $this->rootFolder->getUserFolder($user->getUID());
		$this->sanitizeFiles($folder);
	}

	private function sanitizeFiles(Folder $folder): void {
		foreach ($folder->getDirectoryListing() as $node) {
			$this->output->writeln('scanning: ' . $node->getPath(), Verbosity::Verbose);

			try {
				$oldName = $node->getName();
				$newName = $this->filenameValidator->sanitizeFilename($oldName, $this->charReplacement);
				if ($oldName !== $newName) {
					$newName = $folder->getNonExistingName($newName);
					$path = rtrim(dirname($node->getPath()), '/');

					if (!$this->dryRun) {
						$node->move("$path/$newName");
					} elseif (!$folder->isCreatable()) {
						// simulate error for dry run
						throw new NotPermittedException();
					}
					$this->output->writeln('renamed: "' . $oldName . '" to "' . $newName . '"');
				}
			} catch (LockedException) {
				$this->output->writeln('<comment>skipping: ' . $node->getPath() . ' (file is locked)</>');
			} catch (NotPermittedException) {
				$this->output->writeln('<comment>skipping: ' . $node->getPath() . ' (no permissions)</>');
			} catch (Exception $error) {
				$this->output->writeln('<error>failed: ' . $node->getPath() . '</>');
				$this->output->writeln('<error>' . $error->getMessage() . '</>', Verbosity::Verbose);
			}

			if ($node instanceof Folder) {
				$this->sanitizeFiles($node);
			}
		}
	}
}
