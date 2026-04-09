<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command\Mount;

use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Mount\IMountPoint;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListMounts extends Command {
	public function __construct(
		private readonly IUserManager $userManager,
		private readonly IUserMountCache $userMountCache,
		private readonly IMountProviderCollection $mountProviderCollection,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('files:mount:list')
			->setDescription('List of mounts for a user')
			->addArgument('user', InputArgument::REQUIRED, 'User to list mounts for');
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
		/** @var array<string, IMountPoint> $cachedByMountpoint */
		$mountsByMountpoint = array_combine(array_map(fn (IMountPoint $mount) => $mount->getMountPoint(), $mounts), $mounts);
		usort($mounts, fn (IMountPoint $a, IMountPoint $b) => $a->getMountPoint() <=> $b->getMountPoint());

		$cachedMounts = $this->userMountCache->getMountsForUser($user);
		usort($cachedMounts, fn (ICachedMountInfo $a, ICachedMountInfo $b) => $a->getMountPoint() <=> $b->getMountPoint());
		/** @var array<string, ICachedMountInfo> $cachedByMountpoint */
		$cachedByMountpoint = array_combine(array_map(fn (ICachedMountInfo $mount) => $mount->getMountPoint(), $cachedMounts), $cachedMounts);

		foreach ($mounts as $mount) {
			$output->writeln('<info>' . $mount->getMountPoint() . '</info>: ' . $mount->getStorageId());
			if (isset($cachedByMountpoint[$mount->getMountPoint()])) {
				$cached = $cachedByMountpoint[$mount->getMountPoint()];
				$output->writeln("\t- provider: " . $cached->getMountProvider());
				$output->writeln("\t- storage id: " . $cached->getStorageId());
				$output->writeln("\t- root id: " . $cached->getRootId());
			} else {
				$output->writeln("\t<error>not registered</error>");
			}
		}
		foreach ($cachedMounts as $cachedMount) {
			if (!isset($mountsByMountpoint[$cachedMount->getMountPoint()])) {
				$output->writeln('<info>' . $cachedMount->getMountPoint() . '</info>:');
				$output->writeln("\t<error>registered but no longer provided</error>");
				$output->writeln("\t- provider: " . $cachedMount->getMountProvider());
				$output->writeln("\t- storage id: " . $cachedMount->getStorageId());
				$output->writeln("\t- root id: " . $cachedMount->getRootId());
			}
		}

		return 0;
	}

}
