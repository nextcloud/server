<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command\Mount;

use OC\Core\Command\Base;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Mount\IMountPoint;
use OCP\IUserManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListMounts extends Base {
	public function __construct(
		private readonly IUserManager $userManager,
		private readonly IUserMountCache $userMountCache,
		private readonly IMountProviderCollection $mountProviderCollection,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('files:mount:list')
			->setDescription('List of mounts for a user')
			->addArgument('user', InputArgument::REQUIRED, 'User to list mounts for')
			->addOption('cached-only', null, InputOption::VALUE_NONE, 'Only return cached mounts, prevents filesystem setup');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$userId = $input->getArgument('user');
		$cachedOnly = $input->getOption('cached-only');
		$user = $this->userManager->get($userId);
		if (!$user) {
			$output->writeln("<error>User $userId not found</error>");
			return 1;
		}

		if ($cachedOnly) {
			$mounts = [];
		} else {
			$mounts = $this->mountProviderCollection->getMountsForUser($user);
			$mounts[] = $this->mountProviderCollection->getHomeMountForUser($user);
		}
		/** @var array<string, IMountPoint> $cachedByMountPoint */
		$mountsByMountPoint = array_combine(array_map(fn (IMountPoint $mount) => $mount->getMountPoint(), $mounts), $mounts);
		usort($mounts, fn (IMountPoint $a, IMountPoint $b) => $a->getMountPoint() <=> $b->getMountPoint());

		$cachedMounts = $this->userMountCache->getMountsForUser($user);
		usort($cachedMounts, fn (ICachedMountInfo $a, ICachedMountInfo $b) => $a->getMountPoint() <=> $b->getMountPoint());
		/** @var array<string, ICachedMountInfo> $cachedByMountpoint */
		$cachedByMountPoint = array_combine(array_map(fn (ICachedMountInfo $mount) => $mount->getMountPoint(), $cachedMounts), $cachedMounts);

		$format = $input->getOption('output');

		if ($format === self::OUTPUT_FORMAT_PLAIN) {
			foreach ($mounts as $mount) {
				$output->writeln('<info>' . $mount->getMountPoint() . '</info>: ' . $mount->getStorageId());
				if (isset($cachedByMountPoint[$mount->getMountPoint()])) {
					$cached = $cachedByMountPoint[$mount->getMountPoint()];
					$output->writeln("\t- provider: " . $cached->getMountProvider());
					$output->writeln("\t- storage id: " . $cached->getStorageId());
					$output->writeln("\t- root id: " . $cached->getRootId());
				} else {
					$output->writeln("\t<error>not registered</error>");
				}
			}
			foreach ($cachedMounts as $cachedMount) {
				if ($cachedOnly || !isset($mountsByMountPoint[$cachedMount->getMountPoint()])) {
					$output->writeln('<info>' . $cachedMount->getMountPoint() . '</info>:');
					if (!$cachedOnly) {
						$output->writeln("\t<error>registered but no longer provided</error>");
					}
					$output->writeln("\t- provider: " . $cachedMount->getMountProvider());
					$output->writeln("\t- storage id: " . $cachedMount->getStorageId());
					$output->writeln("\t- root id: " . $cachedMount->getRootId());
				}
			}
		} else {
			$cached = array_map(fn (ICachedMountInfo $cachedMountInfo) => [
				'mountpoint' => $cachedMountInfo->getMountPoint(),
				'provider' => $cachedMountInfo->getMountProvider(),
				'storage_id' => $cachedMountInfo->getStorageId(),
				'root_id' => $cachedMountInfo->getRootId(),
			], $cachedMounts);
			$provided = array_map(fn (IMountPoint $cachedMountInfo) => [
				'mountpoint' => $cachedMountInfo->getMountPoint(),
				'provider' => $cachedMountInfo->getMountProvider(),
				'storage_id' => $cachedMountInfo->getStorageId(),
				'root_id' => $cachedMountInfo->getStorageRootId(),
			], $mounts);
			$this->writeArrayInOutputFormat($input, $output, array_filter([
				'cached' => $cached,
				'provided' => $cachedOnly ? null : $provided,
			]));
		}
		return 0;
	}

}
