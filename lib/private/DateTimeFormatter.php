<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use OCP\IDateTimeFormatter;
use OCP\IL10N;

class DateTimeFormatter implements IDateTimeFormatter {
	/**
	 * Constructor
	 *
	 * @param \DateTimeZone $defaultTimeZone Set the timezone for the format
	 * @param IL10N $defaultL10N Set the language for the format
	 */
	public function __construct(
		protected \DateTimeZone $defaultTimeZone,
		protected IL10N $defaultL10N,
	) {
	}

	/**
	 * Get TimeZone to use
	 *
	 * @param \DateTimeZone $timeZone The timezone to use
	 * @return \DateTimeZone The timezone to use, falling back to the current user's timezone
	 */
	protected function getTimeZone($timeZone = null) {
		if ($timeZone === null) {
			$timeZone = $this->defaultTimeZone;
		}

		return $timeZone;
	}

	/**
	 * Get \OCP\IL10N to use
	 *
	 * @param IL10N $l The locale to use
	 * @return IL10N The locale to use, falling back to the current user's locale
	 */
	protected function getLocale($l = null) {
		if ($l === null) {
			$l = $this->defaultL10N;
		}

		return $l;
	}

	/**
	 * Generates a DateTime object with the given timestamp and TimeZone
	 *
	 * @param mixed $timestamp
	 * @param \DateTimeZone $timeZone The timezone to use
	 * @return \DateTime
	 */
	protected function getDateTime($timestamp, ?\DateTimeZone $timeZone = null) {
		if ($timestamp === null) {
			return new \DateTime('now', $timeZone);
		} elseif (!$timestamp instanceof \DateTime) {
			$dateTime = new \DateTime('now', $timeZone);
			$dateTime->setTimestamp($timestamp);
			return $dateTime;
		}
		if ($timeZone) {
			$timestamp->setTimezone($timeZone);
		}
		return $timestamp;
	}

	/**
	 * Formats the date of the given timestamp
	 *
	 * @param int|\DateTime $timestamp Either a Unix timestamp or DateTime object
	 * @param string $format Either 'full', 'long', 'medium' or 'short'
	 *                       full:	e.g. 'EEEE, MMMM d, y'	=> 'Wednesday, August 20, 2014'
	 *                       long:	e.g. 'MMMM d, y'		=> 'August 20, 2014'
	 *                       medium:	e.g. 'MMM d, y'			=> 'Aug 20, 2014'
	 *                       short:	e.g. 'M/d/yy'			=> '8/20/14'
	 *                       The exact format is dependent on the language
	 * @param \DateTimeZone $timeZone The timezone to use
	 * @param IL10N $l The locale to use
	 * @return string Formatted date string
	 */
	public function formatDate($timestamp, $format = 'long', ?\DateTimeZone $timeZone = null, ?IL10N $l = null) {
		return $this->format($timestamp, 'date', $format, $timeZone, $l);
	}

	/**
	 * Formats the date of the given timestamp
	 *
	 * @param int|\DateTime $timestamp Either a Unix timestamp or DateTime object
	 * @param string $format Either 'full', 'long', 'medium' or 'short'
	 *                       full:	e.g. 'EEEE, MMMM d, y'	=> 'Wednesday, August 20, 2014'
	 *                       long:	e.g. 'MMMM d, y'		=> 'August 20, 2014'
	 *                       medium:	e.g. 'MMM d, y'			=> 'Aug 20, 2014'
	 *                       short:	e.g. 'M/d/yy'			=> '8/20/14'
	 *                       The exact format is dependent on the language
	 *                       Uses 'Today', 'Yesterday' and 'Tomorrow' when applicable
	 * @param \DateTimeZone $timeZone The timezone to use
	 * @param IL10N $l The locale to use
	 * @return string Formatted relative date string
	 */
	public function formatDateRelativeDay($timestamp, $format = 'long', ?\DateTimeZone $timeZone = null, ?IL10N $l = null) {
		if (!str_ends_with($format, '^') && !str_ends_with($format, '*')) {
			$format .= '^';
		}

		return $this->format($timestamp, 'date', $format, $timeZone, $l);
	}

	/**
	 * Gives the relative date of the timestamp
	 * Only works for past dates
	 *
	 * @param int|\DateTime $timestamp Either a Unix timestamp or DateTime object
	 * @param int|\DateTime $baseTimestamp Timestamp to compare $timestamp against, defaults to current time
	 * @param IL10N $l The locale to use
	 * @return string Formatted date span. Dates returned are:
	 *                <  1 month	=> Today, Yesterday, n days ago
	 *                < 13 month	=> last month, n months ago
	 *                >= 13 month	=> last year, n years ago
	 */
	public function formatDateSpan($timestamp, $baseTimestamp = null, ?IL10N $l = null) {
		$l = $this->getLocale($l);
		$timestamp = $this->getDateTime($timestamp);
		$timestamp->setTime(0, 0, 0);

		if ($baseTimestamp === null) {
			$baseTimestamp = time();
		}
		$baseTimestamp = $this->getDateTime($baseTimestamp);
		$baseTimestamp->setTime(0, 0, 0);
		$dateInterval = $timestamp->diff($baseTimestamp);

		if ($dateInterval->y == 0 && $dateInterval->m == 0 && $dateInterval->d == 0) {
			return $l->t('today');
		} elseif ($dateInterval->y == 0 && $dateInterval->m == 0 && $dateInterval->d == 1) {
			if ($timestamp > $baseTimestamp) {
				return $l->t('tomorrow');
			} else {
				return $l->t('yesterday');
			}
		} elseif ($dateInterval->y == 0 && $dateInterval->m == 0) {
			if ($timestamp > $baseTimestamp) {
				return $l->n('in %n day', 'in %n days', $dateInterval->d);
			} else {
				return $l->n('%n day ago', '%n days ago', $dateInterval->d);
			}
		} elseif ($dateInterval->y == 0 && $dateInterval->m == 1) {
			if ($timestamp > $baseTimestamp) {
				return $l->t('next month');
			} else {
				return $l->t('last month');
			}
		} elseif ($dateInterval->y == 0) {
			if ($timestamp > $baseTimestamp) {
				return $l->n('in %n month', 'in %n months', $dateInterval->m);
			} else {
				return $l->n('%n month ago', '%n months ago', $dateInterval->m);
			}
		} elseif ($dateInterval->y == 1) {
			if ($timestamp > $baseTimestamp) {
				return $l->t('next year');
			} else {
				return $l->t('last year');
			}
		}
		if ($timestamp > $baseTimestamp) {
			return $l->n('in %n year', 'in %n years', $dateInterval->y);
		} else {
			return $l->n('%n year ago', '%n years ago', $dateInterval->y);
		}
	}

	/**
	 * Formats the time of the given timestamp
	 *
	 * @param int|\DateTime $timestamp Either a Unix timestamp or DateTime object
	 * @param string $format Either 'full', 'long', 'medium' or 'short'
	 *                       full:	e.g. 'h:mm:ss a zzzz'	=> '11:42:13 AM GMT+0:00'
	 *                       long:	e.g. 'h:mm:ss a z'		=> '11:42:13 AM GMT'
	 *                       medium:	e.g. 'h:mm:ss a'		=> '11:42:13 AM'
	 *                       short:	e.g. 'h:mm a'			=> '11:42 AM'
	 *                       The exact format is dependent on the language
	 * @param \DateTimeZone $timeZone The timezone to use
	 * @param IL10N $l The locale to use
	 * @return string Formatted time string
	 */
	public function formatTime($timestamp, $format = 'medium', ?\DateTimeZone $timeZone = null, ?IL10N $l = null) {
		return $this->format($timestamp, 'time', $format, $timeZone, $l);
	}

	/**
	 * Gives the relative past time of the timestamp
	 *
	 * @param int|\DateTime $timestamp Either a Unix timestamp or DateTime object
	 * @param int|\DateTime $baseTimestamp Timestamp to compare $timestamp against, defaults to current time
	 * @param IL10N $l The locale to use
	 * @return string Formatted time span. Dates returned are:
	 *                < 60 sec	=> seconds ago
	 *                <  1 hour	=> n minutes ago
	 *                <  1 day	=> n hours ago
	 *                <  1 month	=> Yesterday, n days ago
	 *                < 13 month	=> last month, n months ago
	 *                >= 13 month	=> last year, n years ago
	 */
	public function formatTimeSpan($timestamp, $baseTimestamp = null, ?IL10N $l = null) {
		$l = $this->getLocale($l);
		$timestamp = $this->getDateTime($timestamp);
		if ($baseTimestamp === null) {
			$baseTimestamp = time();
		}
		$baseTimestamp = $this->getDateTime($baseTimestamp);

		$diff = $timestamp->diff($baseTimestamp);
		if ($diff->y > 0 || $diff->m > 0 || $diff->d > 0) {
			return $this->formatDateSpan($timestamp, $baseTimestamp, $l);
		}

		if ($diff->h > 0) {
			if ($timestamp > $baseTimestamp) {
				return $l->n('in %n hour', 'in %n hours', $diff->h);
			} else {
				return $l->n('%n hour ago', '%n hours ago', $diff->h);
			}
		} elseif ($diff->i > 0) {
			if ($timestamp > $baseTimestamp) {
				return $l->n('in %n minute', 'in %n minutes', $diff->i);
			} else {
				return $l->n('%n minute ago', '%n minutes ago', $diff->i);
			}
		}
		if ($timestamp > $baseTimestamp) {
			return $l->t('in a few seconds');
		} else {
			return $l->t('seconds ago');
		}
	}

	/**
	 * Formats the date and time of the given timestamp
	 *
	 * @param int|\DateTime $timestamp Either a Unix timestamp or DateTime object
	 * @param string $formatDate See formatDate() for description
	 * @param string $formatTime See formatTime() for description
	 * @param \DateTimeZone $timeZone The timezone to use
	 * @param IL10N $l The locale to use
	 * @return string Formatted date and time string
	 */
	public function formatDateTime($timestamp, $formatDate = 'long', $formatTime = 'medium', ?\DateTimeZone $timeZone = null, ?IL10N $l = null) {
		return $this->format($timestamp, 'datetime', $formatDate . '|' . $formatTime, $timeZone, $l);
	}

	/**
	 * Formats the date and time of the given timestamp
	 *
	 * @param int|\DateTime $timestamp Either a Unix timestamp or DateTime object
	 * @param string $formatDate See formatDate() for description
	 *                           Uses 'Today', 'Yesterday' and 'Tomorrow' when applicable
	 * @param string $formatTime See formatTime() for description
	 * @param \DateTimeZone $timeZone The timezone to use
	 * @param IL10N $l The locale to use
	 * @return string Formatted relative date and time string
	 */
	public function formatDateTimeRelativeDay($timestamp, $formatDate = 'long', $formatTime = 'medium', ?\DateTimeZone $timeZone = null, ?IL10N $l = null) {
		if (!str_ends_with($formatDate, '^') && !str_ends_with($formatDate, '*')) {
			$formatDate .= '^';
		}

		return $this->format($timestamp, 'datetime', $formatDate . '|' . $formatTime, $timeZone, $l);
	}

	/**
	 * Formats the date and time of the given timestamp
	 *
	 * @param int|\DateTime $timestamp Either a Unix timestamp or DateTime object
	 * @param string $type One of 'date', 'datetime' or 'time'
	 * @param string $format Format string
	 * @param \DateTimeZone $timeZone The timezone to use
	 * @param IL10N $l The locale to use
	 * @return string Formatted date and time string
	 */
	protected function format($timestamp, $type, $format, ?\DateTimeZone $timeZone = null, ?IL10N $l = null) {
		$l = $this->getLocale($l);
		$timeZone = $this->getTimeZone($timeZone);
		$timestamp = $this->getDateTime($timestamp, $timeZone);

		return $l->l($type, $timestamp, [
			'width' => $format,
		]);
	}
}
