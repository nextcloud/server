<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command\Mount;

use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\Attribute\Option;
use OCP\Console\ExitCode;
use OCP\Console\IInput;
use OCP\Console\IOutput;
use OCP\Console\OutputFormat;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Mount\IMountPoint;
use OCP\IUserManager;

#[AsCommand(
	name: 'files:mount:list',
	description: 'List of mounts for a user',
	supportsOutputFormat: true,
)]
class ListMounts {
	public function __construct(
		private readonly IUserManager $userManager,
		private readonly IUserMountCache $userMountCache,
		private readonly IMountProviderCollection $mountProviderCollection,
	) {
	}

	public function __invoke(
		IInput $input,
		IOutput $output,
		OutputFormat $outputFormat,
		#[Argument(description: 'User to list mounts for')]
		string $user,
		#[Option(name: 'cached-only', description: 'Only return cached mounts, prevents filesystem setup')]
		bool $cachedOnly = false,
	): ExitCode {
		$userId = $user;
		$user = $this->userManager->get($userId);
		if (!$user) {
			$output->writeln("<error>User $userId not found</error>");
			return ExitCode::Failure;
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

		if ($outputFormat === OutputFormat::Plain) {
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
			$output->writeArrayInOutputFormat(array_filter([
				'cached' => $cached,
				'provided' => $cachedOnly ? null : $provided,
			]));
		}
		return ExitCode::Success;
	}
}
