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
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExpireTrash extends Base {

	public function __construct(
		private readonly ?IUserManager $userManager,
		private readonly ?Expiration $expiration,
		private readonly SetupManager $setupManager,
		private readonly IRootFolder $rootFolder,
	) {
		parent::__construct();
	}

	protected function configure(): void {
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

		$userIds = $input->getArgument('user_id');
		if (!empty($userIds)) {
			foreach ($userIds as $userId) {
				$user = $this->userManager->get($userId);
				if ($user) {
					$output->writeln("Remove deleted files of <info>$userId</info>");
					$this->expireTrashForUser($user, $output);
					$output->writeln("<error>Unknown user $userId</error>");
					return 1;
				} else {
					$output->writeln("<error>Unknown user $userId</error>");
					return 1;
				}
			}
		} else {
			$p = new ProgressBar($output);
			$p->start();

			$users = $this->userManager->getSeenUsers();
			foreach ($users as $user) {
				$p->advance();
				$this->expireTrashForUser($user, $output);
			}
			$p->finish();
			$output->writeln('');
		}
		return 0;
	}

	private function expireTrashForUser(IUser $user, OutputInterface $output): void {
		try {
			$trashRoot = $this->getTrashRoot($user);
			Trashbin::expire($trashRoot, $user);
		} catch (\Throwable $e) {
			$output->writeln('<error>Error while expiring trashbin for user ' . $user->getUID() . '</error>');
			throw $e;
		} finally {
			$this->setupManager->tearDown();
		}
	}

	private function getTrashRoot(IUser $user): Folder {
		$this->setupManager->setupForUser($user);

		$folder = $this->rootFolder->getUserFolder($user->getUID())->getParent()->get('files_trashbin');
		if (!$folder instanceof Folder) {
			throw new \LogicException("Didn't expect files_trashbin to be a file instead of a folder");
		}
		return $folder;
	}
}
