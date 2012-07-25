<?php
/**
 * ownCloud - Journal
 *
 * @copyright 2012 Thomas Tanghus <thomas@tanghus.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * This class manages our journal.
 */
class OC_Journal_Hooks {
	/**
	 * Hook to convert a completed Task (VTODO) to a journal entry and add it to the calendar.
	 * @param $vtodo An OC_VObject of type VTODO.
	 */
	public static function taskToJournalEntry($vtodo) {
		if(!$vtodo) { return; }
		
		OCP\Util::writeLog('journal', 'Completed task: '.$vtodo->getAsString('SUMMARY'), OCP\Util::DEBUG);
		$vcalendar = OC_Journal_App::createVCalendar();
		$vjournal = $vcalendar->VJOURNAL;
		$vjournal->setDateTime('DTSTART',$vtodo->COMPLETED->getDateTime());
		$vjournal->SUMMARY = $vtodo->SUMMARY;
		$vjournal->setString('SUMMARY', OC_Journal_App::$l10n->t('Completed task: ').$vjournal->getAsString('SUMMARY'));
		$vjournal->DESCRIPTION = $vtodo->DESCRIPTION;

		$cid = OCP\Config::getUserValue(OCP\User::getUser(), 'journal', 'default_calendar', null);
		if(!$cid) {
			$calendars = OC_Calendar_Calendar::allCalendars(OCP\User::getUser(), true);
			$first_calendar = reset($calendars);
			$cid = $first_calendar['id'];
		}
		try {
			$id = OC_Calendar_Object::add($cid, $vcalendar->serialize());
		} catch (Exception $e) {
			OCP\Util::writeLog('journal', 'Error adding completed Task to calendar: "'.$cid.'" '. $e->getMessage(), OCP\Util::ERROR);
		}
	}
}
