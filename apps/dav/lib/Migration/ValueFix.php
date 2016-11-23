<?php
/**
 * @copyright 2016, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\Migration;

use OC\BackgroundJob\QueuedJob;
use OCA\DAV\CalDAV\CalDavBackend;
use OCP\ILogger;
use Sabre\VObject\InvalidDataException;

class ValueFix extends QueuedJob {

	/** @var CalDavBackend */
	private $calDavBackend;

	/** @var ILogger */
	private $logger;

	public function __construct(CalDavBackend $calDavBackend, ILogger $logger) {
		$this->calDavBackend = $calDavBackend;
		$this->logger = $logger;
	}

	public function run($argument) {
		$user = $argument['user'];

		$pattern = '/;VALUE=:/';
 		$principal = 'principals/users/' . $user;
		$calendars = $this->calDavBackend->getCalendarsForUser($principal);
		foreach ($calendars as $calendar) {
			$objects = $this->calDavBackend->getCalendarObjects($calendar['id']);
			foreach ($objects as $object) {
				$calObject = $this->calDavBackend->getCalendarObject($calendar['id'], $object['uri']);
				$data = preg_replace($pattern, ':', $calObject['calendardata']);
				if ($data !== $calObject['calendardata']) {
					try {
						$this->calDavBackend->getDenormalizedData($data);
					} catch (InvalidDataException $e) {
						$this->logger->info('Calendar object for calendar {cal} with uri {uri} still invalid', [
							'app'=> 'dav',
							'cal' => $calendar['id'],
							'uri' => $object['uri'],
						]);
						continue;
					}
					$this->calDavBackend->updateCalendarObject($calendar['id'], $object['uri'], $data);
				}
			}
		}
	}

}
