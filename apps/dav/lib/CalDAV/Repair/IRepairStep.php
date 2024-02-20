<?php
/**
 * @copyright 2023, Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\CalDAV\Repair;

use Sabre\VObject\Component\VCalendar;

interface IRepairStep {
	/**
	 * Returns true if the step will be run on new data as well as updated one
	 */
	public function runOnCreate(): bool;

	/**
	 * The callback to implement while checking. If it runs on create, beware that oldObject will logically be null for this condition.
	 * Fix the updated object by editing the $newObject and setting $modified to true.
	 */
	public function onCalendarObjectChange(?VCalendar $oldVCalendar, ?VCalendar $newVCalendar, bool &$modified): void;
}
