<?php
/**
 * @author Thomas Citharel <tcit@tcit.fr>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Command;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\Connector\Sabre\Principal;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MoveCalendar extends Command {

	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager $groupManager */
	private $groupManager;

	/** @var IConfig $config */
	private $config;

	/** @var IL10N */
	private $l10n;

	/** @var SymfonyStyle */
	private $io;

	/** @var CalDavBackend */
	private $calDav;

	const URI_USERS = 'principals/users/';

	/**
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param IConfig $config
	 * @param IL10N $l10n
	 * @param CalDavBackend $calDav
	 */
	function __construct(
		IUserManager $userManager,
		IGroupManager $groupManager,
		IConfig $config,
		IL10N $l10n,
		CalDavBackend $calDav
	) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->config = $config;
		$this->l10n = $l10n;
		$this->calDav = $calDav;
	}

	protected function configure() {
		$this
			->setName('dav:move-calendar')
			->setDescription('Move a calendar from an user to another')
			->addArgument('name',
				InputArgument::REQUIRED,
				'Name of the calendar to move')
			->addArgument('userorigin',
				InputArgument::REQUIRED,
				'User who currently owns the calendar')
			->addArgument('userdestination',
				InputArgument::REQUIRED,
				'User who will receive the calendar')
			->addOption('force', 'f', InputOption::VALUE_NONE, "Force the migration by removing shares with groups that the destination user is not in");
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$userOrigin = $input->getArgument('userorigin');
		$userDestination = $input->getArgument('userdestination');

		$this->io = new SymfonyStyle($input, $output);

		if (in_array('system', [$userOrigin, $userDestination], true)) {
			throw new \InvalidArgumentException("User can't be system");
		}

		if (!$this->userManager->userExists($userOrigin)) {
			throw new \InvalidArgumentException("User <$userOrigin> is unknown.");
		}


		if (!$this->userManager->userExists($userDestination)) {
			throw new \InvalidArgumentException("User <$userDestination> is unknown.");
		}

		$name = $input->getArgument('name');

		$calendar = $this->calDav->getCalendarByUri(self::URI_USERS . $userOrigin, $name);

		if (null === $calendar) {
			throw new \InvalidArgumentException("User <$userOrigin> has no calendar named <$name>. You can run occ dav:list-calendars to list calendars URIs for this user.");
		}

		if (null !== $this->calDav->getCalendarByUri(self::URI_USERS . $userDestination, $name)) {
			throw new \InvalidArgumentException("User <$userDestination> already has a calendar named <$name>.");
		}

		$this->checkShares($calendar, $userDestination, $input->getOption('force'));

		$this->calDav->moveCalendar($name, self::URI_USERS . $userOrigin, self::URI_USERS . $userDestination);

		$this->io->success("Calendar <$name> was moved from user <$userOrigin> to <$userDestination>");
	}

	/**
	 * Check that user destination is member of the groups which whom the calendar was shared
	 * If we ask to force the migration, the share with the group is dropped
	 *
	 * @param $calendar
	 * @param $userDestination
	 * @param bool $force
	 */
	private function checkShares($calendar, $userDestination, $force = false)
	{
		$shares = $this->calDav->getShares($calendar['id']);
		foreach ($shares as $share) {
			list(, $prefix, $group) = explode('/', $share['href'], 3);
			if ('groups' === $prefix && !$this->groupManager->isInGroup($userDestination, $group)) {
				if ($force) {
					$this->calDav->updateShares(new Calendar($this->calDav, $calendar, $this->l10n, $this->config), [], ['href' => 'principal:principals/groups/' . $group]);
				} else {
					throw new \InvalidArgumentException("User <$userDestination> is not part of the group <$group> with whom the calendar <" . $calendar['uri'] . "> was shared. You may use -f to move the calendar while deleting this share.");
				}
			}
		}
	}
}
