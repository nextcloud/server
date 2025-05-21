<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Command;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Sharing\Backend;
use OCA\DAV\CalDAV\Sharing\Service;
use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\DAV\Sharing\Backend as BackendAlias;
use OCA\DAV\DAV\Sharing\SharingMapper;
use OCP\IAppConfig;
use OCP\IUserManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
	name: 'dav:clear-calendar-unshares',
	description: 'Clear calendar unshares for a user',
	hidden: false,
)]
class ClearCalendarUnshares extends Command {
	public function __construct(
		private IUserManager $userManager,
		private IAppConfig $appConfig,
		private Principal $principal,
		private CalDavBackend $caldav,
		private Backend $sharingBackend,
		private Service $sharingService,
		private SharingMapper $mapper,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->addArgument(
			'uid',
			InputArgument::REQUIRED,
			'User whose unshares to clear'
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$user = (string)$input->getArgument('uid');
		if (!$this->userManager->userExists($user)) {
			throw new \InvalidArgumentException("User $user is unknown");
		}

		$principal = $this->principal->getPrincipalByPath('principals/users/' . $user);
		if ($principal === null) {
			throw new \InvalidArgumentException("Unable to fetch principal for user $user ");
		}

		$shares = $this->mapper->getSharesByPrincipals([$principal['uri']], 'calendar');
		$unshares = array_filter($shares, static fn ($share) => $share['access'] === BackendAlias::ACCESS_UNSHARED);

		if (count($unshares) === 0) {
			$output->writeln("User $user has no calendar unshares");
			return self::SUCCESS;
		}

		$rows = array_map(fn ($share) => $this->formatCalendarUnshare($share), $shares);

		$table = new Table($output);
		$table
			->setHeaders(['Share Id', 'Calendar Id', 'Calendar URI', 'Calendar Name'])
			->setRows($rows)
			->render();

		$output->writeln('');

		/** @var QuestionHelper $helper */
		$helper = $this->getHelper('question');
		$question = new ConfirmationQuestion('Please confirm to delete the above calendar unshare entries [y/n]', false);

		if ($helper->ask($input, $output, $question)) {
			$this->mapper->deleteUnsharesByPrincipal($principal['uri'], 'calendar');
			$output->writeln("Calendar unshares for user $user deleted");
		}

		return self::SUCCESS;
	}

	private function formatCalendarUnshare(array $share): array {
		$calendarInfo = $this->caldav->getCalendarById($share['resourceid']);

		$resourceUri = 'Resource not found';
		$resourceName = '';

		if ($calendarInfo !== null) {
			$resourceUri = $calendarInfo['uri'];
			$resourceName = $calendarInfo['{DAV:}displayname'];
		}

		return [
			$share['id'],
			$share['resourceid'],
			$resourceUri,
			$resourceName,
		];
	}
}
