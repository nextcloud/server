<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Versions\Command;

use OCA\Files_Versions\Db\VersionsMapper;
use OCP\Files\IRootFolder;
use OCP\IUserBackend;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanUp extends Command {
	public function __construct(
		protected IRootFolder $rootFolder,
		protected IUserManager $userManager,
		protected VersionsMapper $versionMapper,
	) {
		parent::__construct();
	}

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
				if (!$this->userManager->userExists($user)) {
					$output->writeln("<error>Unknown user $user</error>");
					return self::FAILURE;
				}

				$output->writeln("Delete versions of   <info>$user</info>");
				$this->deleteVersions($user, $path);
			}
			return self::SUCCESS;
		}

		$output->writeln('Delete all versions');
		foreach ($this->userManager->getBackends() as $backend) {
			$name = get_class($backend);

			if ($backend instanceof IUserBackend) {
				$name = $backend->getBackendName();
			}

			$output->writeln("Delete versions for users on backend <info>$name</info>");

			$limit = 500;
			$offset = 0;
			do {
				$users = $backend->getUsers('', $limit, $offset);
				foreach ($users as $user) {
					$output->writeln("   <info>$user</info>");
					$this->deleteVersions($user);
				}
				$offset += $limit;
			} while (count($users) >= $limit);
		}

		return self::SUCCESS;
	}


	/**
	 * delete versions for the given user
	 */
	protected function deleteVersions(string $user, ?string $path = null): void {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($user);

		$userHomeStorageId = $this->rootFolder->getUserFolder($user)->getStorage()->getCache()->getNumericStorageId();
		$this->versionMapper->deleteAllVersionsForUser($userHomeStorageId, $path);

		$fullPath = '/' . $user . '/files_versions' . ($path ? '/' . $path : '');
		if ($this->rootFolder->nodeExists($fullPath)) {
			$this->rootFolder->get($fullPath)->delete();
		}
	}
}
