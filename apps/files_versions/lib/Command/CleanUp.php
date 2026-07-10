<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_Versions\Command;

use OCA\Files_Versions\Db\VersionsMapper;
use OCP\Files\IRootFolder;
use OCP\Files\ISetupManager;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanUp extends Command {
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly IUserManager $userManager,
		private readonly VersionsMapper $versionMapper,
		private readonly ISetupManager $setupManager,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		$this
			->setName('versions:cleanup')
			->setDescription('Delete versions')
			->addArgument(
				'user_id',
				InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
				'delete versions of the given user(s), if no user is given all versions will be deleted'
			)
			->addOption(
				'path',
				'p',
				InputOption::VALUE_REQUIRED,
				'only delete versions of this path, e.g. --path="/alice/files/Music"'
			);
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$users = $input->getArgument('user_id');

		$path = $input->getOption('path');
		if ($path) {
			if (!preg_match('#^/([^/]+)/files(/.*)?$#', $path, $pathMatches)) {
				$output->writeln('<error>Invalid path given</error>');
				return self::FAILURE;
			}

			$users = [ $pathMatches[1] ];
			$path = trim($pathMatches[2], '/');
		}

		if (!empty($users)) {
			foreach ($users as $user) {
				$userObject = $this->userManager->get($user);
				if ($userObject === null) {
					$output->writeln("<error>Unknown user $user</error>");
					return self::FAILURE;
				}

				$output->writeln("Delete versions of   <info>$user</info>");
				$this->deleteVersions($userObject, $path);
			}
			return self::SUCCESS;
		}

		$output->writeln('Delete all versions');
		$this->userManager->callForAllUsers(function (IUser $user) use ($output) {
			$output->writeln('   <info>' . $user->getUID() . '</info>');
			$this->deleteVersions($user);
		});

		return self::SUCCESS;
	}

	/**
	 * delete versions for the given user
	 */
	protected function deleteVersions(IUser $user, ?string $path = null): void {
		$this->setupManager->tearDown();
		$this->setupManager->setupForUser($user);

		$userHomeStorageId = $this->rootFolder->getUserFolder($user->getUID())->getStorage()->getCache()->getNumericStorageId();
		$this->versionMapper->deleteAllVersionsForUser($userHomeStorageId, $path);

		$fullPath = '/' . $user->getUID() . '/files_versions' . ($path ? '/' . $path : '');
		if ($this->rootFolder->nodeExists($fullPath)) {
			$this->rootFolder->get($fullPath)->delete();
		}
	}
}
