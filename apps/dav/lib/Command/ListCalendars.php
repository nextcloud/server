<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018, Georg Ehrke
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\Command;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\CalDAV\CalDavBackend;
use OCP\Command\Command;
use OCP\Command\IConfiguration;
use OCP\Command\IInput;
use OCP\Command\IOutput;
use OCP\IUserManager;

class ListCalendars extends Command {

	/** @var IUserManager */
	protected $userManager;

	/** @var CalDavBackend */
	private $caldav;

	public function __construct(IUserManager $userManager, CalDavBackend $caldav) {
		parent::__construct(Application::APP_ID);
		$this->userManager = $userManager;
		$this->caldav = $caldav;
	}

	public function getName(): string {
		return 'list-calendars';
	}

	public function getDescription(): string {
		return 'List all calendars of a user';
	}

	public function configure(IConfiguration $configuration): void {
		$configuration->addArgument(
			'uid',
			true,
			'User for whom all calendars will be listed'
		);
	}

	public function execute(IInput $input, IOutput $output): int {
		$user = $input->getArgument('uid');
		if (!$this->userManager->userExists($user)) {
			throw new \InvalidArgumentException("User <$user> is unknown.");
		}

		$calendars = $this->caldav->getCalendarsForUser("principals/users/$user");

		$calendarTableData = [];
		foreach ($calendars as $calendar) {
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
			//$table = new Table($output);
			//$table->setHeaders(['uri', 'displayname', 'owner\'s userid', 'owner\'s displayname', 'writable'])
			//	->setRows($calendarTableData);

			//$table->render();
		} else {
			$output->writeln("<info>User <$user> has no calendars</info>");
		}
		return 0;
	}
}
