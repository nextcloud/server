<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Command;

use Exception;
use OC\Core\Command\Base;
use OC\Files\FilenameValidator;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Lock\LockedException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SanitizeFilenames extends Base {

	private OutputInterface $output;
	private string $charReplacement;
	private bool $dryRun;

	public function __construct(
		private IUserManager $userManager,
		private IRootFolder $rootFolder,
		private IUserSession $session,
		private IFactory $l10nFactory,
		private FilenameValidator $filenameValidator,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();

		$forbiddenCharacter = $this->filenameValidator->getForbiddenCharacters();
		$charReplacement = array_diff([' ', '_', '-'], $forbiddenCharacter);
		$charReplacement = reset($charReplacement) ?: '';

		$this
			->setName('files:sanitize-filenames')
			->setDescription('Renames files to match naming constraints')
			->addArgument(
				'user_id',
				InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
				'will only rename files the given user(s) have access to'
			)
			->addOption(
				'dry-run',
				mode: InputOption::VALUE_NONE,
				description: 'Do not actually rename any files but just check filenames.',
			)
			->addOption(
				'char-replacement',
				'c',
				mode: InputOption::VALUE_REQUIRED,
				description: 'Replacement for invalid character (by default space, underscore or dash is used)',
				default: $charReplacement,
			);
			
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->charReplacement = $input->getOption('char-replacement');
		if ($this->charReplacement === '' || mb_strlen($this->charReplacement) > 1) {
			$output->writeln('<error>No character replacement given</error>');
			return 1;
		}

		$this->dryRun = $input->getOption('dry-run');
		if ($this->dryRun) {
			$output->writeln('<info>Dry run is enabled, no actual renaming will be applied.</>');
		}

		$this->output = $output;
		$users = $input->getArgument('user_id');
		if (!empty($users)) {
			foreach ($users as $userId) {
				$user = $this->userManager->get($userId);
				if ($user === null) {
					$output->writeln("<error>User '$userId' does not exist - skipping</>");
					continue;
				}
				$this->sanitizeUserFiles($user);
			}
		} else {
			$this->userManager->callForSeenUsers($this->sanitizeUserFiles(...));
		}
		return self::SUCCESS;
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
			$this->output->writeln('scanning: ' . $node->getPath(), OutputInterface::VERBOSITY_VERBOSE);

			try {
				$oldName = $node->getName();
				if (!$this->filenameValidator->isFilenameValid($oldName)) {
					$newName = $this->sanitizeName($oldName);
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
			} catch (Exception) {
				$this->output->writeln('<error>failed: ' . $node->getPath() . '</>');
			}

			if ($node instanceof Folder) {
				$this->sanitizeFiles($node);
			}
		}
	}

	private function sanitizeName(string $name): string {
		$l10n = $this->l10nFactory->get('files');

		foreach ($this->filenameValidator->getForbiddenExtensions() as $extension) {
			if (str_ends_with($name, $extension)) {
				$name = substr($name, 0, strlen($name) - strlen($extension));
			}
		}

		$basename = substr($name, 0, strpos($name, '.', 1) ?: null);
		if (in_array($basename, $this->filenameValidator->getForbiddenBasenames())) {
			$name = str_replace($basename, $l10n->t('%1$s (renamed)', [$basename]), $name);
		}

		if ($name === '') {
			$name = $l10n->t('renamed file');
		}

		$forbiddenCharacter = $this->filenameValidator->getForbiddenCharacters();
		$name = str_replace($forbiddenCharacter, $this->charReplacement, $name);

		return $name;
	}
}
