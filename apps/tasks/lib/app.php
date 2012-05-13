<?php
/**
 * ownCloud - Calendar
 *
 * @author Bart Visscher
 * @copyright 2011 Bart Visscher bartv@thisnet.nl
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
 * This class manages our tasks
 */
OC_Task_App::$l10n = new OC_L10N('tasks');
class OC_Task_App {
	public static $l10n;

	public static function getPriorityOptions()
	{
		return array(
			''  => self::$l10n->t('Unspecified'),
			'1' => self::$l10n->t('1=highest'),
			'2' => '2',
			'3' => '3',
			'4' => '4',
			'5' => self::$l10n->t('5=medium'),
			'6' => '6',
			'7' => '7',
			'8' => '8',
			'9' => self::$l10n->t('9=lowest'),
		);
	}

	public static function arrayForJSON($id, $vtodo, $user_timezone)
	{
                $task = array( 'id' => $id );
		$task['summary'] = $vtodo->getAsString('SUMMARY');
		$task['description'] = $vtodo->getAsString('DESCRIPTION');
		$task['location'] = $vtodo->getAsString('LOCATION');
		$task['categories'] = $vtodo->getAsArray('CATEGORIES');
		$due = $vtodo->DUE;
		if ($due) {
			$task['due_date_only'] = $due->getDateType() == Sabre_VObject_Element_DateTime::DATE;
			$due = $due->getDateTime();
			$due->setTimezone(new DateTimeZone($user_timezone));
			$task['due'] = $due->format('U');
		}
		else {
			$task['due'] = false;
		}
		$task['priority'] = $vtodo->getAsString('PRIORITY');
		$completed = $vtodo->COMPLETED;
		if ($completed) {
			$completed = $completed->getDateTime();
			$completed->setTimezone(new DateTimeZone($user_timezone));
			$task['completed'] = $completed->format('Y-m-d H:i:s');
		}
		else {
			$task['completed'] = false;
		}
		$task['complete'] = $vtodo->getAsString('PERCENT-COMPLETE');
		return $task;
	}

	public static function validateRequest($request)
	{
		$errors = array();
		if($request['summary'] == ''){
			$errors['summary'] = self::$l10n->t('Empty Summary');
		}

		try {
			$timezone = OCP\Config::getUserValue(OCP\User::getUser(), "calendar", "timezone", "Europe/London");
			$timezone = new DateTimeZone($timezone);
			new DateTime($request['due'], $timezone);
		} catch (Exception $e) {
			$errors['due'] = self::$l10n->t('Invalid date/time');
		}

		if ($request['percent_complete'] < 0 || $request['percent_complete'] > 100){
			$errors['percent_complete'] = self::$l10n->t('Invalid percent complete');
		}
		if ($request['percent_complete'] == 100 && !empty($request['completed'])){
			try {
				$timezone = OCP\Config::getUserValue(OCP\User::getUser(), "calendar", "timezone", "Europe/London");
				$timezone = new DateTimeZone($timezone);
				new DateTime($request['completed'], $timezone);
			} catch (Exception $e) {
				$errors['completed'] = self::$l10n->t('Invalid date/time');
			}
		}

		$priority_options = self::getPriorityOptions();
		if (!in_array($request['priority'], array_keys($priority_options))) {
			$errors['priority'] = self::$l10n->t('Invalid priority');
		}
		return $errors;
	}

	public static function createVCalendarFromRequest($request)
	{
		$vcalendar = new OC_VObject('VCALENDAR');
		$vcalendar->add('PRODID', 'ownCloud Calendar');
		$vcalendar->add('VERSION', '2.0');

		$vtodo = new OC_VObject('VTODO');
		$vcalendar->add($vtodo);

		$vtodo->setDateTime('CREATED', 'now', Sabre_VObject_Element_DateTime::UTC);

		$vtodo->setUID();
		return self::updateVCalendarFromRequest($request, $vcalendar);
	}

	public static function updateVCalendarFromRequest($request, $vcalendar)
	{
		$summary = $request['summary'];
		$categories = $request["categories"];
		$priority = $request['priority'];
		$percent_complete = $request['percent_complete'];
		$completed = $request['completed'];
		$location = $request['location'];
		$due = $request['due'];
		$description = $request['description'];

		$vtodo = $vcalendar->VTODO;

		$vtodo->setDateTime('LAST-MODIFIED', 'now', Sabre_VObject_Element_DateTime::UTC);
		$vtodo->setDateTime('DTSTAMP', 'now', Sabre_VObject_Element_DateTime::UTC);
		$vtodo->setString('SUMMARY', $summary);

		$vtodo->setString('LOCATION', $location);
		$vtodo->setString('DESCRIPTION', $description);
		$vtodo->setString('CATEGORIES', $categories);
		$vtodo->setString('PRIORITY', $priority);

		if ($due) {
			$timezone = OCP\Config::getUserValue(OCP\User::getUser(), 'calendar', 'timezone', date_default_timezone_get());
			$timezone = new DateTimeZone($timezone);
			$due = new DateTime($due, $timezone);
			$vtodo->setDateTime('DUE', $due);
		} else {
			unset($vtodo->DUE);
		}

		self::setComplete($vtodo, $percent_complete, $completed);

		return $vcalendar;
	}

	public static function setComplete($vtodo, $percent_complete, $completed)
	{
		if (!empty($percent_complete)) {
			$vtodo->setString('PERCENT-COMPLETE', $percent_complete);
		}else{
			$vtodo->__unset('PERCENT-COMPLETE');
		}

		if ($percent_complete == 100){
			if (!$completed){
				$completed = 'now';
			}
		} else {
			$completed = null;
		}
		if ($completed) {
			$timezone = OCP\Config::getUserValue(OCP\User::getUser(), 'calendar', 'timezone', date_default_timezone_get());
			$timezone = new DateTimeZone($timezone);
			$completed = new DateTime($completed, $timezone);
			$vtodo->setDateTime('COMPLETED', $completed);
		} else {
			unset($vtodo->COMPLETED);
		}
	}
}
