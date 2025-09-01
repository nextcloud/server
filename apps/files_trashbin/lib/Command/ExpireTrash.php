<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud GmbH.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin\Command;

use OC\Files\SetupManager;
use OCA\Files_Trashbin\Expiration;
use OCA\Files_Trashbin\Service\ExpireService;
use OCP\Files\IRootFolder;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExpireTrash extends Command {
	public function __construct(
		readonly SetupManager $setupManager,
		readonly IRootFolder $rootFolder,
		readonly private IUserManager $userManager,
		readonly private Expiration $expiration,
		readonly private ExpireService $expireService,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('trashbin:expire')
			->setDescription('Expires the users trashbin')
			->addArgument(
				'user_id',
				InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
				'expires the trashbin of the given user(s), if no user is given the trash for all users will be expired'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$minAge = $this->expiration->getMinAgeAsTimestamp();
		$maxAge = $this->expiration->getMaxAgeAsTimestamp();
		if ($minAge === false && $maxAge === false) {
			$output->writeln('Auto expiration is configured - keeps files and folders in the trash bin for 30 days and automatically deletes anytime after that if space is needed (note: files may not be deleted if space is not needed)');
			return 1;
		}

		$users = $input->getArgument('user_id');
		if (!empty($users)) {
			foreach ($users as $user) {
				$userObject = $this->userManager->get($user);
				if ($userObject) {
					$output->writeln("Remove deleted files of <info>$user</info>");
					$this->expireService->expireTrashForUser($userObject);
					$this->setupManager->tearDown();
				} else {
					$output->writeln("<error>Unknown user $user</error>");
					return 1;
				}
			}
		} else {
			$p = new ProgressBar($output);
			$p->start();

			$users = $this->userManager->getSeenUsers();
			foreach ($users as $user) {
				$p->advance();
				try {
					$this->expireService->expireTrashForUser($user);
					$this->setupManager->tearDown();
				} catch (\Throwable $e) {
					$displayName = $user->getDisplayName();
					$output->writeln("<error>Error while expiring trashbin for user $displayName</error>");
					throw $e;
				}
			}
			$p->finish();
			$output->writeln('');
		}
		return 0;
	}
}
