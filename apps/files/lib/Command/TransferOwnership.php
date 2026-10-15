<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files\Command;

use OCA\Files\Exception\TransferOwnershipException;
use OCA\Files\Service\OwnershipTransferService;
use OCA\Files_External\Config\ConfigAdapter;
use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\Attribute\Option;
use OCP\Console\ExitCode;
use OCP\Console\IInput;
use OCP\Console\IOutput;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IMountPoint;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;

#[AsCommand(
	name: 'files:transfer-ownership',
	description: 'All files and folders are moved to another user - outgoing shares and incoming user file shares (optionally) are moved as well.',
)]
class TransferOwnership {
	public function __construct(
		private IUserManager $userManager,
		private OwnershipTransferService $transferService,
		private IConfig $config,
		private IMountManager $mountManager,
	) {
	}

	public function __invoke(
		IOutput $output,
		IInput $input,
		#[Argument(name: 'source-user', description: 'owner of files which shall be moved')]
		string $sourceUser,
		#[Argument(name: 'destination-user', description: 'user who will be the new owner of the files')]
		string $destinationUser,
		#[Option(description: 'selectively provide the path to transfer. For example --path="folder_name"')]
		string $path = '',
		#[Option(description: 'move data from source user to root directory of destination user, which must be empty')]
		bool $move = false,
		#[Option(name: 'transfer-incoming-shares', description: 'Incoming shares are always transferred now, so this option does not affect the ownership transfer anymore')]
		string|bool $transferIncomingShares = false,
		#[Option(name: 'include-external-storage', description: 'include files on external storages, this will _not_ setup an external storage for the target user, but instead moves all the files from the external storages into the target users home directory')]
		bool $includeExternalStorage = false,
		#[Option(name: 'force-include-external-storage', description: "don't ask for confirmation for transferring external storages")]
		bool $forceIncludeExternalStorage = false,
		#[Option(name: 'use-user-id', description: 'use user ID instead of display name in the transferred folder name')]
		bool $useUserId = false,
	): ExitCode {
		/**
		 * Check if source and destination users are same. If they are same then just ignore the transfer.
		 */
		if ($sourceUser === $destinationUser) {
			$output->writeln("<error>Ownership can't be transferred when Source and Destination users are the same user. Please check your input.</error>");
			return ExitCode::Failure;
		}

		$sourceUserObject = $this->userManager->get($sourceUser);
		$destinationUserObject = $this->userManager->get($destinationUser);

		if (!$sourceUserObject instanceof IUser) {
			$output->writeln('<error>Unknown source user ' . $sourceUser . '</error>');
			return ExitCode::Failure;
		}

		if (!$destinationUserObject instanceof IUser) {
			$output->writeln('<error>Unknown destination user ' . $destinationUser . '</error>');
			return ExitCode::Failure;
		}

		$normalizedPath = ltrim($path, '/');
		if ($includeExternalStorage) {
			$mounts = $this->mountManager->findIn('/' . rtrim($sourceUserObject->getUID() . '/files/' . $normalizedPath, '/'));
			/** @var IMountPoint[] $mounts */
			$mounts = array_filter($mounts, fn ($mount) => $mount->getMountProvider() === ConfigAdapter::class);
			if (count($mounts) > 0) {
				$output->writeln(count($mounts) . ' external storages will be transferred:');
				foreach ($mounts as $mount) {
					$output->writeln('  - <info>' . $mount->getMountPoint() . '</info>');
				}
				$output->writeln('');
				$output->writeln('<comment>Any other users with access to these external storages will lose access to the files.</comment>');
				$output->writeln('');
				if (!$forceIncludeExternalStorage) {
					if (!$input->confirm('Are you sure you want to transfer external storages? (y/N) ', false)) {
						return ExitCode::Failure;
					}
				}
			}
		}

		try {
			$this->transferService->transfer(
				$sourceUserObject,
				$destinationUserObject,
				$normalizedPath,
				$output,
				$move,
				false,
				$includeExternalStorage,
				$useUserId,
			);
		} catch (TransferOwnershipException $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			$exitCode = $e->getCode() !== 0 ? ExitCode::tryFrom($e->getCode()) : null;
			return $exitCode ?? ExitCode::Failure;
		}

		return ExitCode::Success;
	}
}
