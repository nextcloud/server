<?php
/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev@georgswebsite.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
/*
 * This class manages the caching of repeating events
 * Events will be cached for the current year ± 5 years
 */
class OC_Calendar_Repeat{
	/*
	 * @brief returns the cache of an event
	 * @param (int) $id - id of the event
	 * @return (array) 
	 */
	public static function get($id){
		$stmt = OCP\DB::prepare('SELECT * FROM *PREFIX*calendar_repeat WHERE eventid = ?');
		$result = $stmt->execute(array($id));
		$return = array();
		while($row = $result->fetchRow()){
			$return[] = $row;
		}
		return $return;
	}
	/*
	 * @brief returns the cache of an event in a specific peroid
	 * @param (int) $id - id of the event
	 * @param (string) $from - start for period in UTC
	 * @param (string) $until - end for period in UTC
	 * @return (array)
	 */
	public static function get_inperiod($id, $from, $until){
		$stmt = OCP\DB::prepare( 'SELECT * FROM *PREFIX*calendar_repeat WHERE eventid = ?'
		.' AND ((startdate >= ? AND startdate <= ?)'
		.' OR (enddate >= ? AND enddate <= ?)');
		$result = $stmt->execute(array($id,
					$from, $until,
					$from, $until));
		$return = array();
		while($row = $result->fetchRow()){
			$return[] = $row;
		}
		return $return;
	}
	/*
	 * @brief returns the cache of all repeating events of a calendar
	 * @param (int) $id - id of the calendar
	 * @return (array) 
	 */
	public static function getcalendar($id){
		$stmt = OCP\DB::prepare('SELECT * FROM *PREFIX*calendar_repeat WHERE calid = ?');
		$result = $stmt->execute(array($id));
		$return = array();
		while($row = $result->fetchRow()){
			$return[] = $row;
		}
		return $return;
	}
	/*
	 * @brief returns the cache of all repeating events of a calendar in a specific period
	 * @param (int) $id - id of the event
	 * @param (string) $from - start for period in UTC
	 * @param (string) $until - end for period in UTC
	 * @return (array)
	 */
	public static function getcalendar_inperiod($id, $from, $until){
		$stmt = OCP\DB::prepare( 'SELECT * FROM *PREFIX*calendar_repeat WHERE calid = ?'
		.' AND ((startdate >= ? AND startdate <= ?)'
		.' OR (enddate >= ? AND enddate <= ?)');
		$result = $stmt->execute(array($id,
					$from, $until,
					$from, $until));
		$return = array();
		while($row = $result->fetchRow()){
			$return[] = $row;
		}
		return $return;
	}
	/*
	 * @brief generates the cache the first time
	 * @param (int) id - id of the event
	 * @return (bool)
	 */
	public static function generate($id){
		$event = OC_Calendar_Object::find(id);
		if($event['repeating'] == 0){
			return false;
		}
		$object = OC_VObject::parse($event['calendardata']);
		$start = new DateTime('first day of January', new DateTimeZone('UTC'));
		$start->modify('-5 years');
		$end = new DateTime('last day of December', new DateTimeZone('UTC'));
		$end->modify('+5 years');
		$object->expand($start, $end);

		
	}
	/*
	 * @brief generates the cache the first time for all repeating event of an calendar
	 * @param (int) id - id of the calendar
	 * @return (bool)
	 */
	public static function generatecalendar($id){
		$allobjects = OC_Calendar_Object::all($id);
		foreach($allobjects['id'] as $eventid){
			self::generate($eventid);
		}
		return true;
	}
	/*
	 * @brief updates an event that is already cached
	 * @param (int) id - id of the event
	 * @return (bool)
	 */
	public static function update($id){
		self::clean($id);
		self::generate($id);
		return true;
	}
	/*
	 * @brief updates all repating events of a calendar that are already cached
	 * @param (int) id - id of the calendar
	 * @return (bool)
	 */
	public static function updatecalendar($id){
		self::cleancalendar($id);
		self::generatecalendar($id);
		return true;
	}
	/*
	 * @brief checks if an event is already cached
	 * @param (int) id - id of the event
	 * @return (bool)
	 */
	public static function is_cached($id){
		if(count(self::get($id)) === 1){
			return true;
		}else{
			return false;
		}
	}
	/*
	 * @brief checks if a whole calendar is already cached
	 * @param (int) id - id of the calendar
	 * @return (bool)
	 */
	public static function is_calendar_cached($id){
		$cachedevents = count(self::getcalendar($id));
		$repeatingevents = 0;
		$allevents = OC_Calendar_Object::all($id);
		foreach($allevents['repeating'] as $repeating){
			if($repeating === 1){
				$repeatingevents++;
			}
		}
		if($cachedevents < $repeatingevents){
			return false;
		}else{
			return true;
		}
	}
	/*
	 * @brief removes the cache of an event
	 * @param (int) id - id of the event
	 * @return (bool)
	 */
	public static function clean($id){
		$stmt = OCP\DB::prepare('DELETE FROM *PREFIX*calendar_repeat WHERE eventid = ?');
		$stmt->execute(array($id));
	}
	/*
	 * @brief removes the cache of all events of a calendar
	 * @param (int) id - id of the calendar
	 * @return (bool)
	 */
	public static function cleancalendar($id){
		$stmt = OCP\DB::prepare('DELETE FROM *PREFIX*calendar_repeat WHERE calid = ?');
		$stmt->execute(array($id));
	}
}