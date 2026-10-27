<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Command;

use NCU\Sharing\Share;
use NCU\Sharing\ShareUserStatus;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UpdateShareUserStatus extends SharingBase {
	#[\Override]
	public function configure(): void {
		$this
			->setName('sharing:update-share-user-status')
			->setDescription('Update the user status for a share.')
			->addArgument('id', InputArgument::REQUIRED, 'Share ID')
			->addArgument('user-status', InputArgument::REQUIRED, 'User status');
		parent::configure();
	}

	#[\Override]
	public function execute(InputInterface $input, OutputInterface $output): int {
		/** @var string $id */
		$id = $input->getArgument('id');
		/** @var string $userStatus */
		$userStatus = $input->getArgument('user-status');
		$userStatus = ShareUserStatus::from($userStatus);

		return $this->wrapExecution($input, $output, function () use ($id, $userStatus): Share {
			$share = $this->manager->getShare($this->accessContext, $id);
			return $this->manager->updateShareUserStatus($this->accessContext, $share, $userStatus);
		});
	}
}
