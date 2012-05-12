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
	 */
	public static function get();
	/*
	* @brief returns the cache of an event in a specific peroid
	*/
	public static function get_inperiod();
	/*
	 * @brief returns the cache of all events of a calendar
	 */
	public static function getcalendar();
	/*
	* @brief returns the cache of all events of a calendar in a specific period
	*/
	public static function getcalendar_inperiod();
	/*
	 * @brief generates the cache the first time
	 */
	public static function generate();
	/*
	 * @brief updates an event that is already cached
	 */
	public static function update();
	/*
	 * @brief checks if an event is already cached
	 */
	public static function is_cached();
	/*
	 * @brief removes the cache of an event
	 */
	public static function clean();
	/*
	 * @brief removes the cache of all events of a calendar
	 */
	public static function cleancalendar();
}
