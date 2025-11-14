<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command\Mount;

use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\Config\IUserMountCache;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Refresh extends Command {
	public function __construct(
		private readonly IUserManager $userManager,
		private readonly IUserMountCache $userMountCache,
		private readonly IMountProviderCollection $mountProviderCollection,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('files:mount:refresh')
			->setDescription('Refresh the list of mounts for a user')
			->addArgument('user', InputArgument::REQUIRED, 'User to refresh mounts for');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$userId = $input->getArgument('user');
		$user = $this->userManager->get($userId);
		if (!$user) {
			$output->writeln("<error>User $userId not found</error>");
			return 1;
		}

		$mounts = $this->mountProviderCollection->getMountsForUser($user);
		$mounts[] = $this->mountProviderCollection->getHomeMountForUser($user);

		$this->userMountCache->registerMounts($user, $mounts);

		$output->writeln('Registered <info>' . count($mounts) . '</info> mounts');

		return 0;
	}

}
