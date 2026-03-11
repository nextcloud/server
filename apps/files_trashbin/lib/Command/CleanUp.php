<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin\Command;

use OC\Core\Command\Base;
use OC\Files\SetupManager;
use OC\User\LazyUser;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserBackend;
use OCP\IUserManager;
use OCP\Util;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanUp extends Base {

	public function __construct(
		protected IRootFolder $rootFolder,
		protected IUserManager $userManager,
		protected IDBConnection $dbConnection,
		protected SetupManager $setupManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('trashbin:cleanup')
			->setDescription('Remove deleted files')
			->addArgument(
				'user_id',
				InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
				'remove deleted files of the given user(s)'
			)
			->addOption(
				'all-users',
				null,
				InputOption::VALUE_NONE,
				'run action on all users'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$userIds = $input->getArgument('user_id');
		$verbose = $input->getOption('verbose');
		if (!empty($userIds) && $input->getOption('all-users')) {
			throw new InvalidOptionException('Either specify a user_id or --all-users');
		} elseif (!empty($userIds)) {
			foreach ($userIds as $userId) {
				$user = $this->userManager->get($userId);
				if ($user) {
					$output->writeln("Remove deleted files of   <info>$userId</info>");
					$this->removeDeletedFiles($user, $output, $verbose);
				} else {
					$output->writeln("<error>Unknown user $userId</error>");
					return 1;
				}
			}
		} elseif ($input->getOption('all-users')) {
			$output->writeln('Remove deleted files for all users');
			foreach ($this->userManager->getBackends() as $backend) {
				$name = get_class($backend);
				if ($backend instanceof IUserBackend) {
					$name = $backend->getBackendName();
				}
				$output->writeln("Remove deleted files for users on backend <info>$name</info>");
				$limit = 500;
				$offset = 0;
				do {
					$userIds = $backend->getUsers('', $limit, $offset);
					foreach ($userIds as $userId) {
						$output->writeln("   <info>$userId</info>");
						$user = new LazyUser($userId, $this->userManager, null, $backend);
						$this->removeDeletedFiles($user, $output, $verbose);
					}
					$offset += $limit;
				} while (count($userIds) >= $limit);
			}
		} else {
			throw new InvalidOptionException('Either specify a user_id or --all-users');
		}
		return 0;
	}

	/**
	 * Remove deleted files for the given user.
	 */
	protected function removeDeletedFiles(IUser $user, OutputInterface $output, bool $verbose): void {
		$this->setupManager->tearDown();
		$this->setupManager->setupForUser($user);
		$path = '/' . $user->getUID() . '/files_trashbin';
		try {
			$node = $this->rootFolder->get($path);
		} catch (NotFoundException|NotPermittedException) {
			if ($verbose) {
				$output->writeln("No trash found for <info>{$user->getUID()}</info>");
			}
			return;
		}

		if ($verbose) {
			$output->writeln('Deleting <info>' . Util::humanFileSize($node->getSize()) . "</info> in trash for <info>{$user->getUID()}</info>.");
		}
		$node->delete();
		if ($this->rootFolder->nodeExists($path)) {
			$output->writeln('<error>Trash folder sill exists after attempting to delete it</error>');
			return;
		}
		$query = $this->dbConnection->getQueryBuilder();
		$query->delete('files_trash')
			->where($query->expr()->eq('user', $query->createParameter('uid')))
			->setParameter('uid', $user->getUID());
		$query->executeStatement();
	}
}
