<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Command;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\IAppConfig;
use OCP\IUserManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: 'dav:list-subscriptions',
	description: 'List all calendar subscriptions for a user',
	hidden: false,
)]
class ListSubscriptions extends Command {
	public function __construct(
		private IUserManager $userManager,
		private IAppConfig $appConfig,
		private CalDavBackend $caldav,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->addArgument(
			'uid',
			InputArgument::REQUIRED,
			'User whose calendar subscriptions will be listed'
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$user = (string)$input->getArgument('uid');
		if (!$this->userManager->userExists($user)) {
			throw new \InvalidArgumentException("User $user is unknown");
		}

		$defaultRefreshRate = $this->appConfig->getValueString('dav', 'calendarSubscriptionRefreshRate', 'P1D');
		$subscriptions = $this->caldav->getSubscriptionsForUser("principals/users/$user");
		$rows = [];

		foreach ($subscriptions as $subscription) {
			$rows[] = [
				$subscription['uri'],
				$subscription['{DAV:}displayname'],
				$subscription['{http://apple.com/ns/ical/}refreshrate'] ?? ($defaultRefreshRate . ' (default)'),
				$subscription['source'],
			];
		}

		usort($rows, static fn (array $a, array $b) => $a[0] <=> $b[0]);

		if (count($rows) > 0) {
			$table = new Table($output);
			$table
				->setHeaders(['URI', 'Displayname', 'Refresh rate', 'Source'])
				->setRows($rows)
				->render();
		} else {
			$output->writeln("User $user has no subscriptions");
		}

		return self::SUCCESS;
	}
}
