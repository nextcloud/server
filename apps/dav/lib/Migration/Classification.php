<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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


namespace OCA\DAV\Migration;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class Classification implements IRepairStep {

	/** @var CalDavBackend */
	private $calDavBackend;

	/** @var IUserManager */
	private $userManager;

	/**
	 * Classification constructor.
	 *
	 * @param CalDavBackend $calDavBackend
	 */
	public function __construct(CalDavBackend $calDavBackend, IUserManager $userManager) {
		$this->calDavBackend = $calDavBackend;
		$this->userManager = $userManager;
	}

	/**
	 * @param IUser $user
	 */
	public function runForUser($user) {
		$principal = 'principals/users/' . $user->getUID();
		$calendars = $this->calDavBackend->getCalendarsForUser($principal);
		foreach ($calendars as $calendar) {
			$objects = $this->calDavBackend->getCalendarObjects($calendar['id']);
			foreach ($objects as $object) {
				$calObject = $this->calDavBackend->getCalendarObject($calendar['id'], $object['uri']);
				$classification = $this->extractClassification($calObject['calendardata']);
				$this->calDavBackend->setClassification($object['id'], $classification);
			}
		}
	}

	/**
	 * @param $calendarData
	 * @return integer
	 * @throws \Sabre\DAV\Exception\BadRequest
	 */
	protected function extractClassification($calendarData) {
		return $this->calDavBackend->getDenormalizedData($calendarData)['classification'];
	}

	/**
	 * @inheritdoc
	 */
	public function getName() {
		return 'Fix classification for calendar objects';
	}

	/**
	 * @inheritdoc
	 */
	public function run(IOutput $output) {
		$output->startProgress();
		$this->userManager->callForAllUsers(function($user) use ($output) {
			/** @var IUser $user */
			$output->advance(1, $user->getDisplayName());
			$this->runForUser($user);
		});
		$output->finishProgress();
	}
}
