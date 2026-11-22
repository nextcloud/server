<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command\Mount;

use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\Config\IUserMountCache;
use OCP\IUserManager;

#[AsCommand(
	name: 'files:mount:refresh',
	description: 'Refresh the list of mounts for a user',
)]
class Refresh {
	public function __construct(
		private readonly IUserManager $userManager,
		private readonly IUserMountCache $userMountCache,
		private readonly IMountProviderCollection $mountProviderCollection,
	) {
	}

	public function __invoke(
		IOutput $output,
		#[Argument(description: 'User to refresh mounts for')]
		string $user,
	): ExitCode {
		$userId = $user;
		$user = $this->userManager->get($userId);
		if (!$user) {
			$output->writeln("<error>User $userId not found</error>");
			return ExitCode::Failure;
		}

		$mounts = $this->mountProviderCollection->getMountsForUser($user);
		$mounts[] = $this->mountProviderCollection->getHomeMountForUser($user);

		$this->userMountCache->registerMounts($user, $mounts);

		$output->writeln('Registered <info>' . count($mounts) . '</info> mounts');

		return ExitCode::Success;
	}
}
