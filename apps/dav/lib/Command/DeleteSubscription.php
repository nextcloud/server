<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Command;

use OCA\DAV\CalDAV\CachedSubscription;
use OCA\DAV\CalDAV\CalDavBackend;
use OCP\IUserManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: 'dav:delete-subscription',
	description: 'Delete a calendar subscription for a user',
	hidden: false,
)]
class DeleteSubscription extends Command {
	public function __construct(
		private CalDavBackend $calDavBackend,
		private IUserManager $userManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->addArgument(
				'uid',
				InputArgument::REQUIRED,
				'User who owns the calendar subscription'
			)
			->addArgument(
				'uri',
				InputArgument::REQUIRED,
				'URI of the calendar to be deleted'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$user = (string)$input->getArgument('uid');
		if (!$this->userManager->userExists($user)) {
			throw new \InvalidArgumentException("User $user is unknown");
		}

		$uri = (string)$input->getArgument('uri');
		if ($uri === '') {
			throw new \InvalidArgumentException('Specify the URI of the calendar to be deleted');
		}

		$subscriptionInfo = $this->calDavBackend->getSubscriptionByUri(
			'principals/users/' . $user,
			$uri
		);

		if ($subscriptionInfo === null) {
			throw new \InvalidArgumentException("User $user has no calendar subscription with the URI $uri");
		}

		$subscription = new CachedSubscription(
			$this->calDavBackend,
			$subscriptionInfo,
		);

		$subscription->delete();

		$output->writeln("Calendar subscription with the URI $uri for user $user deleted");

		return self::SUCCESS;
	}
}
