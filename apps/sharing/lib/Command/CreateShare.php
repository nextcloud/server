<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Command;

use OCP\IUserManager;
use OCP\Server;
use OCP\Sharing\Exception\ShareInvalidException;
use OCP\Sharing\ShareAccessContext;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CreateShare extends SharingBase {
	#[\Override]
	public function configure(): void {
		$this
			->setName('sharing:create-share')
			->setDescription('Create a new share')
			->addArgument('owner', InputArgument::REQUIRED, 'User ID of the owner');
	}

	#[\Override]
	public function execute(InputInterface $input, OutputInterface $output): int {
		return $this->wrapExecution($output, function () use ($input): string {
			/** @var string $ownerUid */
			$ownerUid = $input->getArgument('owner');
			$owner = Server::get(IUserManager::class)->get($ownerUid);
			if ($owner === null) {
				throw new ShareInvalidException('The owner does not exist: ' . $ownerUid);
			}

			return $this->manager->createShare(new ShareAccessContext($owner));
		});
	}
}
