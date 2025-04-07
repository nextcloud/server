<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Command;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Sharing\Backend;
use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\DAV\Sharing\SharingMapper;
use OCP\IUserManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: 'dav:list-calendar-shares',
	description: 'List all calendar shares for a user',
	hidden: false,
)]
class ListCalendarShares extends Command {
	public function __construct(
		private IUserManager $userManager,
		private Principal $principal,
		private CalDavBackend $caldav,
		private SharingMapper $mapper,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->addArgument(
			'uid',
			InputArgument::REQUIRED,
			'User whose calendar shares will be listed'
		);
		$this->addOption(
			'calendar-id',
			'',
			InputOption::VALUE_REQUIRED,
			'List only shares for the given calendar id id',
			null,
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$user = (string)$input->getArgument('uid');
		if (!$this->userManager->userExists($user)) {
			throw new \InvalidArgumentException("User $user is unknown");
		}

		$principal = $this->principal->getPrincipalByPath('principals/users/' . $user);
		if ($principal === null) {
			throw new \InvalidArgumentException("Unable to fetch principal for user $user");
		}

		$memberships = array_merge(
			[$principal['uri']],
			$this->principal->getGroupMembership($principal['uri']),
			$this->principal->getCircleMembership($principal['uri']),
		);

		$shares = $this->mapper->getSharesByPrincipals($memberships, 'calendar');

		$calendarId = $input->getOption('calendar-id');
		if ($calendarId !== null) {
			$shares = array_filter($shares, fn ($share) => $share['resourceid'] === (int)$calendarId);
		}

		$rows = array_map(fn ($share) => $this->formatCalendarShare($share), $shares);

		if (count($rows) > 0) {
			$table = new Table($output);
			$table
				->setHeaders(['Share Id', 'Calendar Id', 'Calendar URI', 'Calendar Name', 'Calendar Owner', 'Access By', 'Permissions'])
				->setRows($rows)
				->render();
		} else {
			$output->writeln("User $user has no calendar shares");
		}

		return self::SUCCESS;
	}

	private function formatCalendarShare(array $share): array {
		$calendarInfo = $this->caldav->getCalendarById($share['resourceid']);

		$calendarUri = 'Resource not found';
		$calendarName = '';
		$calendarOwner = '';

		if ($calendarInfo !== null) {
			$calendarUri = $calendarInfo['uri'];
			$calendarName = $calendarInfo['{DAV:}displayname'];
			$calendarOwner = $calendarInfo['{http://nextcloud.com/ns}owner-displayname'] . ' (' . $calendarInfo['principaluri'] . ')';
		}

		$accessBy = match (true) {
			str_starts_with($share['principaluri'], 'principals/users/') => 'Individual',
			str_starts_with($share['principaluri'], 'principals/groups/') => 'Group (' . $share['principaluri'] . ')',
			str_starts_with($share['principaluri'], 'principals/circles/') => 'Team (' . $share['principaluri'] . ')',
			default => $share['principaluri'],
		};

		$permissions = match ($share['access']) {
			Backend::ACCESS_READ => 'Read',
			Backend::ACCESS_READ_WRITE => 'Read/Write',
			Backend::ACCESS_UNSHARED => 'Unshare',
			default => $share['access'],
		};

		return [
			$share['id'],
			$share['resourceid'],
			$calendarUri,
			$calendarName,
			$calendarOwner,
			$accessBy,
			$permissions,
		];
	}
}
