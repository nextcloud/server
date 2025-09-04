<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud GmbH.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin\Command;

use OC\Core\Command\Base;
use OC\Files\SetupManager;
use OCA\Files_Trashbin\Expiration;
use OCA\Files_Trashbin\Trashbin;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExpireTrash extends Base {

	public function __construct(
		readonly private LoggerInterface $logger,
		readonly private ?IUserManager $userManager,
		readonly private ?Expiration $expiration,
		readonly private SetupManager $setupManager,
		readonly private IRootFolder $rootFolder,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();
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
				if ($this->userManager->userExists($user)) {
					$output->writeln("Remove deleted files of   <info>$user</info>");
					$userObject = $this->userManager->get($user);
					$this->expireTrashForUser($userObject);
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
				$this->expireTrashForUser($user);
			}
			$p->finish();
			$output->writeln('');
		}
		return 0;
	}

	public function expireTrashForUser(IUser $user) {
		try {
			$trashRoot = $this->getTrashRoot($user);
			if (!$trashRoot) {
				return;
			}
			Trashbin::expire($trashRoot, $user);
		} catch (\Throwable $e) {
			$this->logger->error('Error while expiring trashbin for user ' . $user->getUID(), ['exception' => $e]);
		}
	}

	protected function getTrashRoot(IUser $user): ?Folder {
		$this->setupManager->tearDown();
		$this->setupManager->setupForUser($user);

		try {
			/** @var Folder $folder */
			$folder = $this->rootFolder->getUserFolder($user->getUID())->getParent()->get('files_trashbin');
			return $folder;
		} catch (NotFoundException|NotPermittedException) {
			return null;
		}
	}
}
