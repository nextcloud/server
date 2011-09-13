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
 * This class manages our calendars
 */
class OC_Task_VTodo extends OC_Calendar_Object{
	public static function getPriorityOptions($l10n)
	{
		return array(
			''  => $l10n->t('Unspecified'),
			'1' => $l10n->t('1=highest'),
			'2' => '2',
			'3' => '3',
			'4' => '4',
			'5' => $l10n->t('5=medium'),
			'6' => '6',
			'7' => '7',
			'8' => '8',
			'9' => $l10n->t('9=lowest'),
		);
	}
	public static function validateRequest($request, $l10n)
	{
		$errors = array();
		if($request['summary'] == ''){
			$errors['summary'] = $l10n->t('Empty Summary');
		}

		if(isset($request['categories']) && !is_array($request['categories'])){
			$errors['categories'] = $l10n->t('Not an array');
		}

		try {
			$timezone = OC_Preferences::getValue(OC_USER::getUser(), "calendar", "timezone", "Europe/London");
			$timezone = new DateTimeZone($timezone);
			new DateTime($request['due'], $timezone);
		} catch (Exception $e) {
			$errors['due'] = $l10n->t('Invalid date/time');
		}

		if ($request['percent_complete'] < 0 || $request['percent_complete'] > 100){
			$errors['percent_complete'] = $l10n->t('Invalid percent complete');
		}
		if ($request['percent_complete'] == 100 && !empty($request['completed'])){
			try {
				$timezone = OC_Preferences::getValue(OC_USER::getUser(), "calendar", "timezone", "Europe/London");
				$timezone = new DateTimeZone($timezone);
				new DateTime($request['completed'], $timezone);
			} catch (Exception $e) {
				$errors['completed'] = $l10n->t('Invalid date/time');
			}
		}

		$priority_options = OC_Task_VTodo::getPriorityOptions($l10n);
		if (!in_array($request['priority'], array_keys($priority_options))) {
			$errors['priority'] = $l10n->t('Invalid priority');
		}
		return $errors;
	}

	public static function createVCalendarFromRequest($request)
	{
		$vcalendar = new Sabre_VObject_Component('VCALENDAR');
		$vcalendar->add('PRODID', 'ownCloud Calendar');
		$vcalendar->add('VERSION', '2.0');

		$now = new DateTime();

		$vtodo = new Sabre_VObject_Component('VTODO');
		$vcalendar->add($vtodo);

		$created = new Sabre_VObject_Element_DateTime('CREATED');
		$created->setDateTime($now, Sabre_VObject_Element_DateTime::UTC);
		$vtodo->add($created);

		$uid = self::createUID();
		$vtodo->add('UID',$uid);

		return self::updateVCalendarFromRequest($request, $vcalendar);
	}

	public static function updateVCalendarFromRequest($request, $vcalendar)
	{
		$summary = $request['summary'];
		$categories = $request['categories'];
		$priority = $request['priority'];
		$percent_complete = $request['percent_complete'];
		$completed = $request['completed'];
		$location = $request['location'];
		$due = $request['due'];
		$description = $request['description'];

		$now = new DateTime();
		$vtodo = $vcalendar->VTODO[0];

		$last_modified = new Sabre_VObject_Element_DateTime('LAST-MODIFIED');
		$last_modified->setDateTime($now, Sabre_VObject_Element_DateTime::UTC);
		$vtodo->__set('LAST-MODIFIED', $last_modified);

		$dtstamp = new Sabre_VObject_Element_DateTime('DTSTAMP');
		$dtstamp->setDateTime($now, Sabre_VObject_Element_DateTime::UTC);
		$vtodo->DTSTAMP = $dtstamp;

		$vtodo->SUMMARY = $summary;

		if ($location != '') {
			$vtodo->LOCATION = $location;
		}else{
			unset($vtodo->LOCATION);
		}

		if ($categories != '') {
			$vtodo->CATEGORIES = join(',',$categories);
		}else{
			unset($vtodo->CATEGORIES);
		}

		if ($priority != '') {
			$vtodo->PRIORITY = $priority;
		}else{
			unset($vtodo->PRIORITY);
		}

		if ($description != '') {
			$vtodo->DESCRIPTION = $description;
		}else{
			unset($vtodo->DESCRIPTION);
		}

		if ($due) {
			$due_property = new Sabre_VObject_Element_DateTime('DUE');
			$timezone = OC_Preferences::getValue(OC_USER::getUser(), "calendar", "timezone", "Europe/London");
			$timezone = new DateTimeZone($timezone);
			$due_property->setDateTime(new DateTime($due, $timezone));
			$vtodo->DUE = $due_property;
		} else {
			unset($vtodo->DUE);
		}

		if (!empty($percent_complete)) {
			$vtodo->__set('PERCENT-COMPLETE', $percent_complete);
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
			$completed_property = new Sabre_VObject_Element_DateTime('COMPLETED');
			$timezone = OC_Preferences::getValue(OC_USER::getUser(), "calendar", "timezone", "Europe/London");
			$timezone = new DateTimeZone($timezone);
			$completed_property->setDateTime(new DateTime($completed, $timezone));
			$vtodo->COMPLETED = $completed_property;
		} else {
			unset($vtodo->COMPLETED);
		}

		return $vcalendar;
	}
}

