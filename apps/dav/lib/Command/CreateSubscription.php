<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Command;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\Theming\ThemingDefaults;
use OCP\IUserManager;
use Sabre\DAV\Xml\Property\Href;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateSubscription extends Command {
	public function __construct(
		protected IUserManager $userManager,
		private CalDavBackend $caldav,
		private ThemingDefaults $themingDefaults,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('dav:create-subscription')
			->setDescription('Create a dav subscription')
			->addArgument('user',
				InputArgument::REQUIRED,
				'User for whom the subscription will be created')
			->addArgument('name',
				InputArgument::REQUIRED,
				'Name of the subscription to create')
			->addArgument('url',
				InputArgument::REQUIRED,
				'Source url of the subscription to create')
			->addArgument('color',
				InputArgument::OPTIONAL,
				'Hex color code for the calendar color');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$user = $input->getArgument('user');
		if (!$this->userManager->userExists($user)) {
			$output->writeln("<error>User <$user> in unknown.</error>");
			return self::FAILURE;
		}

		$name = $input->getArgument('name');
		$url = $input->getArgument('url');
		$color = $input->getArgument('color') ?? $this->themingDefaults->getColorPrimary();
		$subscriptions = $this->caldav->getSubscriptionsForUser("principals/users/$user");

		$exists = array_filter($subscriptions, function ($row) use ($url) {
			return $row['source'] === $url;
		});

		if (!empty($exists)) {
			$output->writeln("<error>Subscription for url <$url> already exists for this user.</error>");
			return self::FAILURE;
		}

		$urlProperty = new Href($url);
		$properties = ['{http://owncloud.org/ns}calendar-enabled' => 1,
			'{DAV:}displayname' => $name,
			'{http://apple.com/ns/ical/}calendar-color' => $color,
			'{http://calendarserver.org/ns/}source' => $urlProperty,
		];
		$this->caldav->createSubscription("principals/users/$user", $name, $properties);
		return self::SUCCESS;
	}

}
