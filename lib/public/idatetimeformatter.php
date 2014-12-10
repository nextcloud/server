<?php
/**
 * ownCloud
 *
 * @author Joas Schilling
 * @copyright 2014 Joas Schilling nickvergessen@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP;

interface IDateTimeFormatter {
	/**
	 * Formats the date of the given timestamp
	 *
	 * @param int|\DateTime		$timestamp
	 * @param string	$format			Either 'full', 'long', 'medium' or 'short'
	 * 				full:	e.g. 'EEEE, MMMM d, y'	=> 'Wednesday, August 20, 2014'
	 * 				long:	e.g. 'MMMM d, y'		=> 'August 20, 2014'
	 * 				medium:	e.g. 'MMM d, y'			=> 'Aug 20, 2014'
	 * 				short:	e.g. 'M/d/yy'			=> '8/20/14'
	 * 				The exact format is dependent on the language
	 * @param \DateTimeZone	$timeZone	The timezone to use
	 * @param \OCP\IL10N	$l			The locale to use
	 * @return string Formatted date string
	 */
	public function formatDate($timestamp, $format = 'long', \DateTimeZone $timeZone = null, \OCP\IL10N $l = null);

	/**
	 * Formats the date of the given timestamp
	 *
	 * @param int|\DateTime		$timestamp
	 * @param string	$format			Either 'full', 'long', 'medium' or 'short'
	 * 				full:	e.g. 'EEEE, MMMM d, y'	=> 'Wednesday, August 20, 2014'
	 * 				long:	e.g. 'MMMM d, y'		=> 'August 20, 2014'
	 * 				medium:	e.g. 'MMM d, y'			=> 'Aug 20, 2014'
	 * 				short:	e.g. 'M/d/yy'			=> '8/20/14'
	 * 				The exact format is dependent on the language
	 * 					Uses 'Today', 'Yesterday' and 'Tomorrow' when applicable
	 * @param \DateTimeZone	$timeZone	The timezone to use
	 * @param \OCP\IL10N	$l			The locale to use
	 * @return string Formatted relative date string
	 */
	public function formatDateRelativeDay($timestamp, $format = 'long', \DateTimeZone $timeZone = null, \OCP\IL10N $l = null);

	/**
	 * Gives the relative date of the timestamp
	 * Only works for past dates
	 *
	 * @param int|\DateTime	$timestamp
	 * @param int|\DateTime	$baseTimestamp	Timestamp to compare $timestamp against, defaults to current time
	 * @return string	Dates returned are:
	 * 				<  1 month	=> Today, Yesterday, n days ago
	 * 				< 13 month	=> last month, n months ago
	 * 				>= 13 month	=> last year, n years ago
	 * @param \OCP\IL10N		$l			The locale to use
	 * @return string Formatted date span
	 */
	public function formatDateSpan($timestamp, $baseTimestamp = null, \OCP\IL10N $l = null);

	/**
	 * Formats the time of the given timestamp
	 *
	 * @param int|\DateTime $timestamp
	 * @param string	$format			Either 'full', 'long', 'medium' or 'short'
	 * 				full:	e.g. 'h:mm:ss a zzzz'	=> '11:42:13 AM GMT+0:00'
	 * 				long:	e.g. 'h:mm:ss a z'		=> '11:42:13 AM GMT'
	 * 				medium:	e.g. 'h:mm:ss a'		=> '11:42:13 AM'
	 * 				short:	e.g. 'h:mm a'			=> '11:42 AM'
	 * 				The exact format is dependent on the language
	 * @param \DateTimeZone	$timeZone	The timezone to use
	 * @param \OCP\IL10N		$l			The locale to use
	 * @return string Formatted time string
	 */
	public function formatTime($timestamp, $format = 'medium', \DateTimeZone $timeZone = null, \OCP\IL10N $l = null);

	/**
	 * Gives the relative past time of the timestamp
	 *
	 * @param int|\DateTime	$timestamp
	 * @param int|\DateTime	$baseTimestamp	Timestamp to compare $timestamp against, defaults to current time
	 * @return string	Dates returned are:
	 * 				< 60 sec	=> seconds ago
	 * 				<  1 hour	=> n minutes ago
	 * 				<  1 day	=> n hours ago
	 * 				<  1 month	=> Yesterday, n days ago
	 * 				< 13 month	=> last month, n months ago
	 * 				>= 13 month	=> last year, n years ago
	 * @param \OCP\IL10N		$l			The locale to use
	 * @return string Formatted time span
	 */
	public function formatTimeSpan($timestamp, $baseTimestamp = null, \OCP\IL10N $l = null);

	/**
	 * Formats the date and time of the given timestamp
	 *
	 * @param int|\DateTime $timestamp
	 * @param string	$formatDate		See formatDate() for description
	 * @param string	$formatTime		See formatTime() for description
	 * @param \DateTimeZone	$timeZone	The timezone to use
	 * @param \OCP\IL10N		$l			The locale to use
	 * @return string Formatted date and time string
	 */
	public function formatDateTime($timestamp, $formatDate = 'long', $formatTime = 'medium', \DateTimeZone $timeZone = null, \OCP\IL10N $l = null);

	/**
	 * Formats the date and time of the given timestamp
	 *
	 * @param int|\DateTime $timestamp
	 * @param string	$formatDate		See formatDate() for description
	 * 					Uses 'Today', 'Yesterday' and 'Tomorrow' when applicable
	 * @param string	$formatTime		See formatTime() for description
	 * @param \DateTimeZone	$timeZone	The timezone to use
	 * @param \OCP\IL10N		$l			The locale to use
	 * @return string Formatted relative date and time string
	 */
	public function formatDateTimeRelativeDay($timestamp, $formatDate = 'long', $formatTime = 'medium', \DateTimeZone $timeZone = null, \OCP\IL10N $l = null);
}
