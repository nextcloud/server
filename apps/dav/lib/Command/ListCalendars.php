<?php
/**
 * @copyright Copyright (c) 2018, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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

use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\Connector\Sabre\Principal;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCalendars extends Command {

	/** @var IUserManager */
	protected $userManager;

	/** @var CalDavBackend */
	private $caldav;

	/**
	 * @param IUserManager $userManager
	 * @param CalDavBackend $caldav
	 */
	function __construct(IUserManager $userManager, CalDavBackend $caldav) {
		parent::__construct();
		$this->userManager = $userManager;
		$this->caldav = $caldav;
	}

	protected function configure() {
		$this
			->setName('dav:list-calendars')
			->setDescription('List all calendars of a user')
			->addArgument('uid',
				InputArgument::REQUIRED,
				'User for whom all calendars will be listed');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$user = $input->getArgument('uid');
		if (!$this->userManager->userExists($user)) {
			throw new \InvalidArgumentException("User <$user> is unknown.");
		}

		$calendars = $this->caldav->getCalendarsForUser("principals/users/$user");

		$calendarTableData = [];
		foreach($calendars as $calendar) {
			// skip birthday calendar
			if ($calendar['uri'] === BirthdayService::BIRTHDAY_CALENDAR_URI) {
				continue;
			}

			$readOnly = false;
			$readOnlyIndex = '{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}read-only';
			if (isset($calendar[$readOnlyIndex])) {
				$readOnly = $calendar[$readOnlyIndex];
			}

			$calendarTableData[] = [
				$calendar['uri'],
				$calendar['{DAV:}displayname'],
				$calendar['{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}owner-principal'],
				$calendar['{' . \OCA\DAV\DAV\Sharing\Plugin::NS_NEXTCLOUD . '}owner-displayname'],
				$readOnly ? ' x ' : ' ✓ ',
			];
		}

		if (count($calendarTableData) > 0) {
			$table = new Table($output);
			$table->setHeaders(['uri', 'displayname', 'owner\'s userid', 'owner\'s displayname', 'writable'])
				->setRows($calendarTableData);

			$table->render();
		} else {
			$output->writeln("<info>User <$user> has no calendars</info>");
		}
	}

}
