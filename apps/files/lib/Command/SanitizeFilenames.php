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
use OCA\Files\Service\SettingsService;
use OCP\AppFramework\Services\IAppConfig;
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
	private ?string $charReplacement;
	private bool $dryRun;
	private bool $errorsOrSkipped = false;

	public function __construct(
		private IUserManager $userManager,
		private IRootFolder $rootFolder,
		private IUserSession $session,
		private IFactory $l10nFactory,
		private FilenameValidator $filenameValidator,
		private SettingsService $service,
		private IAppConfig $appConfig,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();

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
			);

	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->charReplacement = $input->getOption('char-replacement');
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
				return 1;
			}
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
			if ($this->service->hasFilesWindowsSupport() && $this->appConfig->getAppValueInt('sanitize_filenames_status') === 0) {
				// we are done - if this is for sanitizing all users for windows filename support then set this UI flag
				$this->appConfig->setAppValueInt('sanitize_filenames_status', SettingsService::STATUS_WCF_DONE);
			}
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
				$this->output->writeln('<error>' . $error->getMessage() . '</>', OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE);
			}

			if ($node instanceof Folder) {
				$this->sanitizeFiles($node);
			}
		}
	}

}
