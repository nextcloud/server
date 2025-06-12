<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Schedule;

use OC\URLGenerator;
use OCA\DAV\CalDAV\EventReader;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\L10N\IFactory as L10NFactory;
use OCP\Mail\IEMailTemplate;
use OCP\Security\ISecureRandom;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\ITip\Message;
use Sabre\VObject\Parameter;
use Sabre\VObject\Property;
use Sabre\VObject\Recur\EventIterator;

class IMipService {

	private IL10N $l10n;

	/** @var string[] */
	private const STRING_DIFF = [
		'meeting_title' => 'SUMMARY',
		'meeting_description' => 'DESCRIPTION',
		'meeting_url' => 'URL',
		'meeting_location' => 'LOCATION'
	];

	public function __construct(
		private URLGenerator $urlGenerator,
		private IConfig $config,
		private IDBConnection $db,
		private ISecureRandom $random,
		private L10NFactory $l10nFactory,
		private ITimeFactory $timeFactory,
	) {
		$language = $this->l10nFactory->findGenericLanguage();
		$locale = $this->l10nFactory->findLocale($language);
		$this->l10n = $this->l10nFactory->get('dav', $language, $locale);
	}

	/**
	 * @param string|null $senderName
	 * @param string $default
	 * @return string
	 */
	public function getFrom(?string $senderName, string $default): string {
		if ($senderName === null) {
			return $default;
		}

		return $this->l10n->t('%1$s via %2$s', [$senderName, $default]);
	}

	public static function readPropertyWithDefault(VEvent $vevent, string $property, string $default) {
		if (isset($vevent->$property)) {
			$value = $vevent->$property->getValue();
			if (!empty($value)) {
				return $value;
			}
		}
		return $default;
	}

	private function generateDiffString(VEvent $vevent, VEvent $oldVEvent, string $property, string $default): ?string {
		$strikethrough = "<span style='text-decoration: line-through'>%s</span><br />%s";
		if (!isset($vevent->$property)) {
			return $default;
		}
		$newstring = $vevent->$property->getValue();
		if (isset($oldVEvent->$property) && $oldVEvent->$property->getValue() !== $newstring) {
			$oldstring = $oldVEvent->$property->getValue();
			return sprintf($strikethrough, $oldstring, $newstring);
		}
		return $newstring;
	}

	/**
	 * Like generateDiffString() but linkifies the property values if they are urls.
	 */
	private function generateLinkifiedDiffString(VEvent $vevent, VEvent $oldVEvent, string $property, string $default): ?string {
		if (!isset($vevent->$property)) {
			return $default;
		}
		/** @var string|null $newString */
		$newString = $vevent->$property->getValue();
		$oldString = isset($oldVEvent->$property) ? $oldVEvent->$property->getValue() : null;
		if ($oldString !== $newString) {
			return sprintf(
				"<span style='text-decoration: line-through'>%s</span><br />%s",
				$this->linkify($oldString) ?? $oldString ?? '',
				$this->linkify($newString) ?? $newString ?? ''
			);
		}
		return $this->linkify($newString) ?? $newString;
	}

	/**
	 * Convert a given url to a html link element or return null otherwise.
	 */
	private function linkify(?string $url): ?string {
		if ($url === null) {
			return null;
		}
		if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
			return null;
		}

		return sprintf('<a href="%1$s">%1$s</a>', htmlspecialchars($url));
	}

	/**
	 * @param VEvent $vEvent
	 * @param VEvent|null $oldVEvent
	 * @return array
	 */
	public function buildBodyData(VEvent $vEvent, ?VEvent $oldVEvent): array {

		// construct event reader
		$eventReaderCurrent = new EventReader($vEvent);
		$eventReaderPrevious = !empty($oldVEvent) ? new EventReader($oldVEvent) : null;
		$defaultVal = '';
		$data = [];
		$data['meeting_when'] = $this->generateWhenString($eventReaderCurrent);

		foreach (self::STRING_DIFF as $key => $property) {
			$data[$key] = self::readPropertyWithDefault($vEvent, $property, $defaultVal);
		}

		$data['meeting_url_html'] = self::readPropertyWithDefault($vEvent, 'URL', $defaultVal);

		if (($locationHtml = $this->linkify($data['meeting_location'])) !== null) {
			$data['meeting_location_html'] = $locationHtml;
		}

		if (!empty($oldVEvent)) {
			$oldMeetingWhen = $this->generateWhenString($eventReaderPrevious);
			$data['meeting_title_html'] = $this->generateDiffString($vEvent, $oldVEvent, 'SUMMARY', $data['meeting_title']);
			$data['meeting_description_html'] = $this->generateDiffString($vEvent, $oldVEvent, 'DESCRIPTION', $data['meeting_description']);
			$data['meeting_location_html'] = $this->generateLinkifiedDiffString($vEvent, $oldVEvent, 'LOCATION', $data['meeting_location']);

			$oldUrl = self::readPropertyWithDefault($oldVEvent, 'URL', $defaultVal);
			$data['meeting_url_html'] = !empty($oldUrl) && $oldUrl !== $data['meeting_url'] ? sprintf('<a href="%1$s">%1$s</a>', $oldUrl) : $data['meeting_url'];

			$data['meeting_when_html'] = $oldMeetingWhen !== $data['meeting_when'] ? sprintf("<span style='text-decoration: line-through'>%s</span><br />%s", $oldMeetingWhen, $data['meeting_when']) : $data['meeting_when'];
		}
		// generate occurring next string
		if ($eventReaderCurrent->recurs()) {
			$data['meeting_occurring'] = $this->generateOccurringString($eventReaderCurrent);
		}
		return $data;
	}

	/**
	 * @param VEvent $vEvent
	 * @return array
	 */
	public function buildReplyBodyData(VEvent $vEvent): array {
		// construct event reader
		$eventReader = new EventReader($vEvent);
		$defaultVal = '';
		$data = [];
		$data['meeting_when'] = $this->generateWhenString($eventReader);

		foreach (self::STRING_DIFF as $key => $property) {
			$data[$key] = self::readPropertyWithDefault($vEvent, $property, $defaultVal);
		}

		if (($locationHtml = $this->linkify($data['meeting_location'])) !== null) {
			$data['meeting_location_html'] = $locationHtml;
		}

		$data['meeting_url_html'] = $data['meeting_url'] ? sprintf('<a href="%1$s">%1$s</a>', $data['meeting_url']) : '';

		// generate occurring next string
		if ($eventReader->recurs()) {
			$data['meeting_occurring'] = $this->generateOccurringString($eventReader);
		}

		return $data;
	}

	/**
	 * generates a when string based on if a event has an recurrence or not
	 *
	 * @since 30.0.0
	 *
	 * @param EventReader $er
	 *
	 * @return string
	 */
	public function generateWhenString(EventReader $er): string {
		return match ($er->recurs()) {
			true => $this->generateWhenStringRecurring($er),
			false => $this->generateWhenStringSingular($er)
		};
	}

	/**
	 * generates a when string for a non recurring event
	 *
	 * @since 30.0.0
	 *
	 * @param EventReader $er
	 *
	 * @return string
	 */
	public function generateWhenStringSingular(EventReader $er): string {
		// initialize
		$startTime = null;
		$endTime = null;
		// calculate time difference from now to start of event
		$occurring = $this->minimizeInterval($this->timeFactory->getDateTime()->diff($er->recurrenceDate()));
		// extract start date
		$startDate = $this->l10n->l('date', $er->startDateTime(), ['width' => 'full']);
		// time of the day
		if (!$er->entireDay()) {
			$startTime = $this->l10n->l('time', $er->startDateTime(), ['width' => 'short']);
			$startTime .= $er->startTimeZone() != $er->endTimeZone() ? ' (' . $er->startTimeZone()->getName() . ')' : '';
			$endTime = $this->l10n->l('time', $er->endDateTime(), ['width' => 'short']) . ' (' . $er->endTimeZone()->getName() . ')';
		}
		// generate localized when string
		// TRANSLATORS
		// Indicates when a calendar event will happen, shown on invitation emails
		// Output produced in order:
		// In a minute/hour/day/week/month/year on July 1, 2024 for the entire day
		// In a minute/hour/day/week/month/year on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)
		// In 2 minutes/hours/days/weeks/months/years on July 1, 2024 for the entire day
		// In 2 minutes/hours/days/weeks/months/years on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)
		return match ([$occurring['scale'], $endTime !== null]) {
			['past', false] => $this->l10n->t(
				'In the past on %1$s for the entire day',
				[$startDate]
			),
			['minute', false] => $this->l10n->n(
				'In a minute on %1$s for the entire day',
				'In %n minutes on %1$s for the entire day',
				$occurring['interval'],
				[$startDate]
			),
			['hour', false] => $this->l10n->n(
				'In a hour on %1$s for the entire day',
				'In %n hours on %1$s for the entire day',
				$occurring['interval'],
				[$startDate]
			),
			['day', false] => $this->l10n->n(
				'In a day on %1$s for the entire day',
				'In %n days on %1$s for the entire day',
				$occurring['interval'],
				[$startDate]
			),
			['week', false] => $this->l10n->n(
				'In a week on %1$s for the entire day',
				'In %n weeks on %1$s for the entire day',
				$occurring['interval'],
				[$startDate]
			),
			['month', false] => $this->l10n->n(
				'In a month on %1$s for the entire day',
				'In %n months on %1$s for the entire day',
				$occurring['interval'],
				[$startDate]
			),
			['year', false] => $this->l10n->n(
				'In a year on %1$s for the entire day',
				'In %n years on %1$s for the entire day',
				$occurring['interval'],
				[$startDate]
			),
			['past', true] => $this->l10n->t(
				'In the past on %1$s between %2$s - %3$s',
				[$startDate, $startTime, $endTime]
			),
			['minute', true] => $this->l10n->n(
				'In a minute on %1$s between %2$s - %3$s',
				'In %n minutes on %1$s between %2$s - %3$s',
				$occurring['interval'],
				[$startDate, $startTime, $endTime]
			),
			['hour', true] => $this->l10n->n(
				'In a hour on %1$s between %2$s - %3$s',
				'In %n hours on %1$s between %2$s - %3$s',
				$occurring['interval'],
				[$startDate, $startTime, $endTime]
			),
			['day', true] => $this->l10n->n(
				'In a day on %1$s between %2$s - %3$s',
				'In %n days on %1$s between %2$s - %3$s',
				$occurring['interval'],
				[$startDate, $startTime, $endTime]
			),
			['week', true] => $this->l10n->n(
				'In a week on %1$s between %2$s - %3$s',
				'In %n weeks on %1$s between %2$s - %3$s',
				$occurring['interval'],
				[$startDate, $startTime, $endTime]
			),
			['month', true] => $this->l10n->n(
				'In a month on %1$s between %2$s - %3$s',
				'In %n months on %1$s between %2$s - %3$s',
				$occurring['interval'],
				[$startDate, $startTime, $endTime]
			),
			['year', true] => $this->l10n->n(
				'In a year on %1$s between %2$s - %3$s',
				'In %n years on %1$s between %2$s - %3$s',
				$occurring['interval'],
				[$startDate, $startTime, $endTime]
			),
			default => $this->l10n->t('Could not generate when statement')
		};
	}

	/**
	 * generates a when string based on recurrence precision/frequency
	 *
	 * @since 30.0.0
	 *
	 * @param EventReader $er
	 *
	 * @return string
	 */
	public function generateWhenStringRecurring(EventReader $er): string {
		return match ($er->recurringPrecision()) {
			'daily' => $this->generateWhenStringRecurringDaily($er),
			'weekly' => $this->generateWhenStringRecurringWeekly($er),
			'monthly' => $this->generateWhenStringRecurringMonthly($er),
			'yearly' => $this->generateWhenStringRecurringYearly($er),
			'fixed' => $this->generateWhenStringRecurringFixed($er),
		};
	}

	/**
	 * generates a when string for a daily precision/frequency
	 *
	 * @since 30.0.0
	 *
	 * @param EventReader $er
	 *
	 * @return string
	 */
	public function generateWhenStringRecurringDaily(EventReader $er): string {
		
		// initialize
		$interval = (int)$er->recurringInterval();
		$startTime = null;
		$conclusion = null;
		// time of the day
		if (!$er->entireDay()) {
			$startTime = $this->l10n->l('time', $er->startDateTime(), ['width' => 'short']);
			$startTime .= $er->startTimeZone() != $er->endTimeZone() ? ' (' . $er->startTimeZone()->getName() . ')' : '';
			$endTime = $this->l10n->l('time', $er->endDateTime(), ['width' => 'short']) . ' (' . $er->endTimeZone()->getName() . ')';
		}
		// conclusion
		if ($er->recurringConcludes()) {
			$conclusion = $this->l10n->l('date', $er->recurringConcludesOn(), ['width' => 'long']);
		}
		// generate localized when string
		// TRANSLATORS
		// Indicates when a calendar event will happen, shown on invitation emails
		// Output produced in order:
		// Every Day for the entire day
		// Every Day for the entire day until July 13, 2024
		// Every Day between 8:00 AM - 9:00 AM (America/Toronto)
		// Every Day between 8:00 AM - 9:00 AM (America/Toronto) until July 13, 2024
		// Every 3 Days for the entire day
		// Every 3 Days for the entire day until July 13, 2024
		// Every 3 Days between 8:00 AM - 9:00 AM (America/Toronto)
		// Every 3 Days between 8:00 AM - 9:00 AM (America/Toronto) until July 13, 2024
		return match ([($interval > 1), $startTime !== null, $conclusion !== null]) {
			[false, false, false] => $this->l10n->t('Every Day for the entire day'),
			[false, false, true] => $this->l10n->t('Every Day for the entire day until %1$s', [$conclusion]),
			[false, true, false] => $this->l10n->t('Every Day between %1$s - %2$s', [$startTime, $endTime]),
			[false, true, true] => $this->l10n->t('Every Day between %1$s - %2$s until %3$s', [$startTime, $endTime, $conclusion]),
			[true, false, false] => $this->l10n->t('Every %1$d Days for the entire day', [$interval]),
			[true, false, true] => $this->l10n->t('Every %1$d Days for the entire day until %2$s', [$interval, $conclusion]),
			[true, true, false] => $this->l10n->t('Every %1$d Days between %2$s - %3$s', [$interval, $startTime, $endTime]),
			[true, true, true] => $this->l10n->t('Every %1$d Days between %2$s - %3$s until %4$s', [$interval, $startTime, $endTime, $conclusion]),
			default => $this->l10n->t('Could not generate event recurrence statement')
		};

	}

	/**
	 * generates a when string for a weekly precision/frequency
	 *
	 * @since 30.0.0
	 *
	 * @param EventReader $er
	 *
	 * @return string
	 */
	public function generateWhenStringRecurringWeekly(EventReader $er): string {
		
		// initialize
		$interval = (int)$er->recurringInterval();
		$startTime = null;
		$conclusion = null;
		// days of the week
		$days = implode(', ', array_map(function ($value) { return $this->localizeDayName($value); }, $er->recurringDaysOfWeekNamed()));
		// time of the day
		if (!$er->entireDay()) {
			$startTime = $this->l10n->l('time', $er->startDateTime(), ['width' => 'short']);
			$startTime .= $er->startTimeZone() != $er->endTimeZone() ? ' (' . $er->startTimeZone()->getName() . ')' : '';
			$endTime = $this->l10n->l('time', $er->endDateTime(), ['width' => 'short']) . ' (' . $er->endTimeZone()->getName() . ')';
		}
		// conclusion
		if ($er->recurringConcludes()) {
			$conclusion = $this->l10n->l('date', $er->recurringConcludesOn(), ['width' => 'long']);
		}
		// generate localized when string
		// TRANSLATORS
		// Indicates when a calendar event will happen, shown on invitation emails
		// Output produced in order:
		// Every Week on Monday, Wednesday, Friday for the entire day
		// Every Week on Monday, Wednesday, Friday for the entire day until July 13, 2024
		// Every Week on Monday, Wednesday, Friday between 8:00 AM - 9:00 AM (America/Toronto)
		// Every Week on Monday, Wednesday, Friday between 8:00 AM - 9:00 AM (America/Toronto) until July 13, 2024
		// Every 2 Weeks on Monday, Wednesday, Friday for the entire day
		// Every 2 Weeks on Monday, Wednesday, Friday for the entire day until July 13, 2024
		// Every 2 Weeks on Monday, Wednesday, Friday between 8:00 AM - 9:00 AM (America/Toronto)
		// Every 2 Weeks on Monday, Wednesday, Friday between 8:00 AM - 9:00 AM (America/Toronto) until July 13, 2024
		return match ([($interval > 1), $startTime !== null, $conclusion !== null]) {
			[false, false, false] => $this->l10n->t('Every Week on %1$s for the entire day', [$days]),
			[false, false, true] => $this->l10n->t('Every Week on %1$s for the entire day until %2$s', [$days, $conclusion]),
			[false, true, false] => $this->l10n->t('Every Week on %1$s between %2$s - %3$s', [$days, $startTime, $endTime]),
			[false, true, true] => $this->l10n->t('Every Week on %1$s between %2$s - %3$s until %4$s', [$days, $startTime, $endTime, $conclusion]),
			[true, false, false] => $this->l10n->t('Every %1$d Weeks on %2$s for the entire day', [$interval, $days]),
			[true, false, true] => $this->l10n->t('Every %1$d Weeks on %2$s for the entire day until %3$s', [$interval, $days, $conclusion]),
			[true, true, false] => $this->l10n->t('Every %1$d Weeks on %2$s between %3$s - %4$s', [$interval, $days, $startTime, $endTime]),
			[true, true, true] => $this->l10n->t('Every %1$d Weeks on %2$s between %3$s - %4$s until %5$s', [$interval, $days, $startTime, $endTime, $conclusion]),
			default => $this->l10n->t('Could not generate event recurrence statement')
		};

	}

	/**
	 * generates a when string for a monthly precision/frequency
	 *
	 * @since 30.0.0
	 *
	 * @param EventReader $er
	 *
	 * @return string
	 */
	public function generateWhenStringRecurringMonthly(EventReader $er): string {
		
		// initialize
		$interval = (int)$er->recurringInterval();
		$startTime = null;
		$conclusion = null;
		// days of month
		if ($er->recurringPattern() === 'R') {
			$days = implode(', ', array_map(function ($value) { return $this->localizeRelativePositionName($value); }, $er->recurringRelativePositionNamed())) . ' ' .
					implode(', ', array_map(function ($value) { return $this->localizeDayName($value); }, $er->recurringDaysOfWeekNamed()));
		} else {
			$days = implode(', ', $er->recurringDaysOfMonth());
		}
		// time of the day
		if (!$er->entireDay()) {
			$startTime = $this->l10n->l('time', $er->startDateTime(), ['width' => 'short']);
			$startTime .= $er->startTimeZone() != $er->endTimeZone() ? ' (' . $er->startTimeZone()->getName() . ')' : '';
			$endTime = $this->l10n->l('time', $er->endDateTime(), ['width' => 'short']) . ' (' . $er->endTimeZone()->getName() . ')';
		}
		// conclusion
		if ($er->recurringConcludes()) {
			$conclusion = $this->l10n->l('date', $er->recurringConcludesOn(), ['width' => 'long']);
		}
		// generate localized when string
		// TRANSLATORS
		// Indicates when a calendar event will happen, shown on invitation emails
		// Output produced in order, output varies depending on if the event is absolute or releative:
		// Absolute: Every Month on the 1, 8 for the entire day
		// Relative: Every Month on the First Sunday, Saturday for the entire day
		// Absolute: Every Month on the 1, 8 for the entire day until December 31, 2024
		// Relative: Every Month on the First Sunday, Saturday for the entire day until December 31, 2024
		// Absolute: Every Month on the 1, 8 between 8:00 AM - 9:00 AM (America/Toronto)
		// Relative: Every Month on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto)
		// Absolute: Every Month on the 1, 8 between 8:00 AM - 9:00 AM (America/Toronto) until December 31, 2024
		// Relative: Every Month on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto) until December 31, 2024
		// Absolute: Every 2 Months on the 1, 8 for the entire day
		// Relative: Every 2 Months on the First Sunday, Saturday for the entire day
		// Absolute: Every 2 Months on the 1, 8 for the entire day until December 31, 2024
		// Relative: Every 2 Months on the First Sunday, Saturday for the entire day until December 31, 2024
		// Absolute: Every 2 Months on the 1, 8 between 8:00 AM - 9:00 AM (America/Toronto)
		// Relative: Every 2 Months on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto)
		// Absolute: Every 2 Months on the 1, 8 between 8:00 AM - 9:00 AM (America/Toronto) until December 31, 2024
		// Relative: Every 2 Months on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto) until December 31, 2024
		return match ([($interval > 1), $startTime !== null, $conclusion !== null]) {
			[false, false, false] => $this->l10n->t('Every Month on the %1$s for the entire day', [$days]),
			[false, false, true] => $this->l10n->t('Every Month on the %1$s for the entire day until %2$s', [$days, $conclusion]),
			[false, true, false] => $this->l10n->t('Every Month on the %1$s between %2$s - %3$s', [$days, $startTime, $endTime]),
			[false, true, true] => $this->l10n->t('Every Month on the %1$s between %2$s - %3$s until %4$s', [$days, $startTime, $endTime, $conclusion]),
			[true, false, false] => $this->l10n->t('Every %1$d Months on the %2$s for the entire day', [$interval, $days]),
			[true, false, true] => $this->l10n->t('Every %1$d Months on the %2$s for the entire day until %3$s', [$interval, $days, $conclusion]),
			[true, true, false] => $this->l10n->t('Every %1$d Months on the %2$s between %3$s - %4$s', [$interval, $days, $startTime, $endTime]),
			[true, true, true] => $this->l10n->t('Every %1$d Months on the %2$s between %3$s - %4$s until %5$s', [$interval, $days, $startTime, $endTime, $conclusion]),
			default => $this->l10n->t('Could not generate event recurrence statement')
		};
	}

	/**
	 * generates a when string for a yearly precision/frequency
	 *
	 * @since 30.0.0
	 *
	 * @param EventReader $er
	 *
	 * @return string
	 */
	public function generateWhenStringRecurringYearly(EventReader $er): string {
		
		// initialize
		$interval = (int)$er->recurringInterval();
		$startTime = null;
		$conclusion = null;
		// months of year
		$months = implode(', ', array_map(function ($value) { return $this->localizeMonthName($value); }, $er->recurringMonthsOfYearNamed()));
		// days of month
		if ($er->recurringPattern() === 'R') {
			$days = implode(', ', array_map(function ($value) { return $this->localizeRelativePositionName($value); }, $er->recurringRelativePositionNamed())) . ' ' .
					implode(', ', array_map(function ($value) { return $this->localizeDayName($value); }, $er->recurringDaysOfWeekNamed()));
		} else {
			$days = $er->startDateTime()->format('jS');
		}
		// time of the day
		if (!$er->entireDay()) {
			$startTime = $this->l10n->l('time', $er->startDateTime(), ['width' => 'short']);
			$startTime .= $er->startTimeZone() != $er->endTimeZone() ? ' (' . $er->startTimeZone()->getName() . ')' : '';
			$endTime = $this->l10n->l('time', $er->endDateTime(), ['width' => 'short']) . ' (' . $er->endTimeZone()->getName() . ')';
		}
		// conclusion
		if ($er->recurringConcludes()) {
			$conclusion = $this->l10n->l('date', $er->recurringConcludesOn(), ['width' => 'long']);
		}
		// generate localized when string
		// TRANSLATORS
		// Indicates when a calendar event will happen, shown on invitation emails
		// Output produced in order, output varies depending on if the event is absolute or releative:
		// Absolute: Every Year in July on the 1st for the entire day
		// Relative: Every Year in July on the First Sunday, Saturday for the entire day
		// Absolute: Every Year in July on the 1st for the entire day until July 31, 2026
		// Relative: Every Year in July on the First Sunday, Saturday for the entire day until July 31, 2026
		// Absolute: Every Year in July on the 1st between 8:00 AM - 9:00 AM (America/Toronto)
		// Relative: Every Year in July on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto)
		// Absolute: Every Year in July on the 1st between 8:00 AM - 9:00 AM (America/Toronto) until July 31, 2026
		// Relative: Every Year in July on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto) until July 31, 2026
		// Absolute: Every 2 Years in July on the 1st for the entire day
		// Relative: Every 2 Years in July on the First Sunday, Saturday for the entire day
		// Absolute: Every 2 Years in July on the 1st for the entire day until July 31, 2026
		// Relative: Every 2 Years in July on the First Sunday, Saturday for the entire day until July 31, 2026
		// Absolute: Every 2 Years in July on the 1st between 8:00 AM - 9:00 AM (America/Toronto)
		// Relative: Every 2 Years in July on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto)
		// Absolute: Every 2 Years in July on the 1st between 8:00 AM - 9:00 AM (America/Toronto) until July 31, 2026
		// Relative: Every 2 Years in July on the First Sunday, Saturday between 8:00 AM - 9:00 AM (America/Toronto) until July 31, 2026
		return match ([($interval > 1), $startTime !== null, $conclusion !== null]) {
			[false, false, false] => $this->l10n->t('Every Year in %1$s on the %2$s for the entire day', [$months, $days]),
			[false, false, true] => $this->l10n->t('Every Year in %1$s on the %2$s for the entire day until %3$s', [$months, $days, $conclusion]),
			[false, true, false] => $this->l10n->t('Every Year in %1$s on the %2$s between %3$s - %4$s', [$months, $days, $startTime, $endTime]),
			[false, true, true] => $this->l10n->t('Every Year in %1$s on the %2$s between %3$s - %4$s until %5$s', [$months, $days, $startTime, $endTime, $conclusion]),
			[true, false, false] => $this->l10n->t('Every %1$d Years in %2$s on the %3$s for the entire day', [$interval, $months, $days]),
			[true, false, true] => $this->l10n->t('Every %1$d Years in %2$s on the %3$s for the entire day until %4$s', [$interval, $months,  $days, $conclusion]),
			[true, true, false] => $this->l10n->t('Every %1$d Years in %2$s on the %3$s between %4$s - %5$s', [$interval, $months, $days, $startTime, $endTime]),
			[true, true, true] => $this->l10n->t('Every %1$d Years in %2$s on the %3$s between %4$s - %5$s until %6$s', [$interval, $months, $days, $startTime, $endTime, $conclusion]),
			default => $this->l10n->t('Could not generate event recurrence statement')
		};
	}

	/**
	 * generates a when string for a fixed precision/frequency
	 *
	 * @since 30.0.0
	 *
	 * @param EventReader $er
	 *
	 * @return string
	 */
	public function generateWhenStringRecurringFixed(EventReader $er): string {
		// initialize
		$startTime = null;
		$conclusion = null;
		// time of the day
		if (!$er->entireDay()) {
			$startTime = $this->l10n->l('time', $er->startDateTime(), ['width' => 'short']);
			$startTime .= $er->startTimeZone() != $er->endTimeZone() ? ' (' . $er->startTimeZone()->getName() . ')' : '';
			$endTime = $this->l10n->l('time', $er->endDateTime(), ['width' => 'short']) . ' (' . $er->endTimeZone()->getName() . ')';
		}
		// conclusion
		$conclusion = $this->l10n->l('date', $er->recurringConcludesOn(), ['width' => 'long']);
		// generate localized when string
		// TRANSLATORS
		// Indicates when a calendar event will happen, shown on invitation emails
		// Output produced in order:
		// On specific dates for the entire day until July 13, 2024
		// On specific dates between 8:00 AM - 9:00 AM (America/Toronto) until July 13, 2024
		return match ($startTime !== null) {
			false => $this->l10n->t('On specific dates for the entire day until %1$s', [$conclusion]),
			true => $this->l10n->t('On specific dates between %1$s - %2$s until %3$s', [$startTime, $endTime, $conclusion]),
		};
	}
	
	/**
	 * generates a occurring next string for a recurring event
	 *
	 * @since 30.0.0
	 *
	 * @param EventReader $er
	 *
	 * @return string
	 */
	public function generateOccurringString(EventReader $er): string {

		// initialize
		$occurrence = null;
		$occurrence2 = null;
		$occurrence3 = null;
		// reset to initial occurrence
		$er->recurrenceRewind();
		// forward to current date
		$er->recurrenceAdvanceTo($this->timeFactory->getDateTime());
		// calculate time difference from now to start of next event occurrence and minimize it
		$occurrenceIn = $this->minimizeInterval($this->timeFactory->getDateTime()->diff($er->recurrenceDate()));
		// store next occurrence value
		$occurrence = $this->l10n->l('date', $er->recurrenceDate(), ['width' => 'long']);
		// forward one occurrence
		$er->recurrenceAdvance();
		// evaluate if occurrence is valid
		if ($er->recurrenceDate() !== null) {
			// store following occurrence value
			$occurrence2 = $this->l10n->l('date', $er->recurrenceDate(), ['width' => 'long']);
			// forward one occurrence
			$er->recurrenceAdvance();
			// evaluate if occurrence is valid
			if ($er->recurrenceDate()) {
				// store following occurrence value
				$occurrence3 = $this->l10n->l('date', $er->recurrenceDate(), ['width' => 'long']);
			}
		}
		// generate localized when string
		// TRANSLATORS
		// Indicates when a calendar event will happen, shown on invitation emails
		// Output produced in order:
		// In a minute/hour/day/week/month/year on July 1, 2024
		// In a minute/hour/day/week/month/year on July 1, 2024 then on July 3, 2024
		// In a minute/hour/day/week/month/year on July 1, 2024 then on July 3, 2024 and July 5, 2024
		// In 2 minutes/hours/days/weeks/months/years on July 1, 2024
		// In 2 minutes/hours/days/weeks/months/years on July 1, 2024 then on July 3, 2024
		// In 2 minutes/hours/days/weeks/months/years on July 1, 2024 then on July 3, 2024 and July 5, 2024
		return match ([$occurrenceIn['scale'], $occurrence2 !== null, $occurrence3 !== null]) {
			['past', false, false] => $this->l10n->t(
				'In the past on %1$s',
				[$occurrence]
			),
			['minute', false, false] => $this->l10n->n(
				'In a minute on %1$s',
				'In %n minutes on %1$s',
				$occurrenceIn['interval'],
				[$occurrence]
			),
			['hour', false, false] => $this->l10n->n(
				'In a hour on %1$s',
				'In %n hours on %1$s',
				$occurrenceIn['interval'],
				[$occurrence]
			),
			['day', false, false] => $this->l10n->n(
				'In a day on %1$s',
				'In %n days on %1$s',
				$occurrenceIn['interval'],
				[$occurrence]
			),
			['week', false, false] => $this->l10n->n(
				'In a week on %1$s',
				'In %n weeks on %1$s',
				$occurrenceIn['interval'],
				[$occurrence]
			),
			['month', false, false] => $this->l10n->n(
				'In a month on %1$s',
				'In %n months on %1$s',
				$occurrenceIn['interval'],
				[$occurrence]
			),
			['year', false, false] => $this->l10n->n(
				'In a year on %1$s',
				'In %n years on %1$s',
				$occurrenceIn['interval'],
				[$occurrence]
			),
			['past', true, false] => $this->l10n->t(
				'In the past on %1$s then on %2$s',
				[$occurrence, $occurrence2]
			),
			['minute', true, false] => $this->l10n->n(
				'In a minute on %1$s then on %2$s',
				'In %n minutes on %1$s then on %2$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2]
			),
			['hour', true, false] => $this->l10n->n(
				'In a hour on %1$s then on %2$s',
				'In %n hours on %1$s then on %2$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2]
			),
			['day', true, false] => $this->l10n->n(
				'In a day on %1$s then on %2$s',
				'In %n days on %1$s then on %2$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2]
			),
			['week', true, false] => $this->l10n->n(
				'In a week on %1$s then on %2$s',
				'In %n weeks on %1$s then on %2$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2]
			),
			['month', true, false] => $this->l10n->n(
				'In a month on %1$s then on %2$s',
				'In %n months on %1$s then on %2$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2]
			),
			['year', true, false] => $this->l10n->n(
				'In a year on %1$s then on %2$s',
				'In %n years on %1$s then on %2$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2]
			),
			['past', true, true] => $this->l10n->t(
				'In the past on %1$s then on %2$s and %3$s',
				[$occurrence, $occurrence2, $occurrence3]
			),
			['minute', true, true] => $this->l10n->n(
				'In a minute on %1$s then on %2$s and %3$s',
				'In %n minutes on %1$s then on %2$s and %3$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2, $occurrence3]
			),
			['hour', true, true] => $this->l10n->n(
				'In a hour on %1$s then on %2$s and %3$s',
				'In %n hours on %1$s then on %2$s and %3$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2, $occurrence3]
			),
			['day', true, true] => $this->l10n->n(
				'In a day on %1$s then on %2$s and %3$s',
				'In %n days on %1$s then on %2$s and %3$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2, $occurrence3]
			),
			['week', true, true] => $this->l10n->n(
				'In a week on %1$s then on %2$s and %3$s',
				'In %n weeks on %1$s then on %2$s and %3$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2, $occurrence3]
			),
			['month', true, true] => $this->l10n->n(
				'In a month on %1$s then on %2$s and %3$s',
				'In %n months on %1$s then on %2$s and %3$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2, $occurrence3]
			),
			['year', true, true] => $this->l10n->n(
				'In a year on %1$s then on %2$s and %3$s',
				'In %n years on %1$s then on %2$s and %3$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2, $occurrence3]
			),
			default => $this->l10n->t('Could not generate next recurrence statement')
		};

	}

	/**
	 * @param VEvent $vEvent
	 * @return array
	 */
	public function buildCancelledBodyData(VEvent $vEvent): array {
		// construct event reader
		$eventReaderCurrent = new EventReader($vEvent);
		$defaultVal = '';
		$strikethrough = "<span style='text-decoration: line-through'>%s</span>";

		$newMeetingWhen = $this->generateWhenString($eventReaderCurrent);
		$newSummary = isset($vEvent->SUMMARY) && (string)$vEvent->SUMMARY !== '' ? (string)$vEvent->SUMMARY : $this->l10n->t('Untitled event');
		$newDescription = isset($vEvent->DESCRIPTION) && (string)$vEvent->DESCRIPTION !== '' ? (string)$vEvent->DESCRIPTION : $defaultVal;
		$newUrl = isset($vEvent->URL) && (string)$vEvent->URL !== '' ? sprintf('<a href="%1$s">%1$s</a>', $vEvent->URL) : $defaultVal;
		$newLocation = isset($vEvent->LOCATION) && (string)$vEvent->LOCATION !== '' ? (string)$vEvent->LOCATION : $defaultVal;
		$newLocationHtml = $this->linkify($newLocation) ?? $newLocation;

		$data = [];
		$data['meeting_when_html'] = $newMeetingWhen === '' ?: sprintf($strikethrough, $newMeetingWhen);
		$data['meeting_when'] = $newMeetingWhen;
		$data['meeting_title_html'] = sprintf($strikethrough, $newSummary);
		$data['meeting_title'] = $newSummary !== '' ? $newSummary: $this->l10n->t('Untitled event');
		$data['meeting_description_html'] = $newDescription !== '' ? sprintf($strikethrough, $newDescription) : '';
		$data['meeting_description'] = $newDescription;
		$data['meeting_url_html'] = $newUrl !== '' ? sprintf($strikethrough, $newUrl) : '';
		$data['meeting_url'] = isset($vEvent->URL) ? (string)$vEvent->URL : '';
		$data['meeting_location_html'] = $newLocationHtml !== '' ? sprintf($strikethrough, $newLocationHtml) : '';
		$data['meeting_location'] = $newLocation;
		return $data;
	}

	/**
	 * Check if event took place in the past
	 *
	 * @param VCalendar $vObject
	 * @return int
	 */
	public function getLastOccurrence(VCalendar $vObject) {
		/** @var VEvent $component */
		$component = $vObject->VEVENT;

		if (isset($component->RRULE)) {
			$it = new EventIterator($vObject, (string)$component->UID);
			$maxDate = new \DateTime(IMipPlugin::MAX_DATE);
			if ($it->isInfinite()) {
				return $maxDate->getTimestamp();
			}

			$end = $it->getDtEnd();
			while ($it->valid() && $end < $maxDate) {
				$end = $it->getDtEnd();
				$it->next();
			}
			return $end->getTimestamp();
		}

		/** @var Property\ICalendar\DateTime $dtStart */
		$dtStart = $component->DTSTART;

		if (isset($component->DTEND)) {
			/** @var Property\ICalendar\DateTime $dtEnd */
			$dtEnd = $component->DTEND;
			return $dtEnd->getDateTime()->getTimeStamp();
		}

		if (isset($component->DURATION)) {
			/** @var \DateTime $endDate */
			$endDate = clone $dtStart->getDateTime();
			// $component->DTEND->getDateTime() returns DateTimeImmutable
			$endDate = $endDate->add(DateTimeParser::parse($component->DURATION->getValue()));
			return $endDate->getTimestamp();
		}

		if (!$dtStart->hasTime()) {
			/** @var \DateTime $endDate */
			// $component->DTSTART->getDateTime() returns DateTimeImmutable
			$endDate = clone $dtStart->getDateTime();
			$endDate = $endDate->modify('+1 day');
			return $endDate->getTimestamp();
		}

		// No computation of end time possible - return start date
		return $dtStart->getDateTime()->getTimeStamp();
	}

	/**
	 * @param Property|null $attendee
	 */
	public function setL10n(?Property $attendee = null) {
		if ($attendee === null) {
			return;
		}

		$lang = $attendee->offsetGet('LANGUAGE');
		if ($lang instanceof Parameter) {
			$lang = $lang->getValue();
			$this->l10n = $this->l10nFactory->get('dav', $lang);
		}
	}

	/**
	 * @param Property|null $attendee
	 * @return bool
	 */
	public function getAttendeeRsvpOrReqForParticipant(?Property $attendee = null) {
		if ($attendee === null) {
			return false;
		}

		$rsvp = $attendee->offsetGet('RSVP');
		if (($rsvp instanceof Parameter) && (strcasecmp($rsvp->getValue(), 'TRUE') === 0)) {
			return true;
		}
		$role = $attendee->offsetGet('ROLE');
		// @see https://datatracker.ietf.org/doc/html/rfc5545#section-3.2.16
		// Attendees without a role are assumed required and should receive an invitation link even if they have no RSVP set
		if ($role === null
			|| (($role instanceof Parameter) && (strcasecmp($role->getValue(), 'REQ-PARTICIPANT') === 0))
			|| (($role instanceof Parameter) && (strcasecmp($role->getValue(), 'OPT-PARTICIPANT') === 0))
		) {
			return true;
		}

		// RFC 5545 3.2.17: default RSVP is false
		return false;
	}

	/**
	 * @param IEMailTemplate $template
	 * @param string $method
	 * @param string $sender
	 * @param string $summary
	 * @param string|null $partstat
	 * @param bool $isModified
	 */
	public function addSubjectAndHeading(IEMailTemplate $template,
		string $method, string $sender, string $summary, bool $isModified, ?Property $replyingAttendee = null): void {
		if ($method === IMipPlugin::METHOD_CANCEL) {
			// TRANSLATORS Subject for email, when an invitation is cancelled. Ex: "Cancelled: {{Event Name}}"
			$template->setSubject($this->l10n->t('Cancelled: %1$s', [$summary]));
			$template->addHeading($this->l10n->t('"%1$s" has been canceled', [$summary]));
		} elseif ($method === IMipPlugin::METHOD_REPLY) {
			// TRANSLATORS Subject for email, when an invitation is replied to. Ex: "Re: {{Event Name}}"
			$template->setSubject($this->l10n->t('Re: %1$s', [$summary]));
			// Build the strings
			$partstat = (isset($replyingAttendee)) ? $replyingAttendee->offsetGet('PARTSTAT') : null;
			$partstat = ($partstat instanceof Parameter) ? $partstat->getValue() : null;
			switch ($partstat) {
				case 'ACCEPTED':
					$template->addHeading($this->l10n->t('%1$s has accepted your invitation', [$sender]));
					break;
				case 'TENTATIVE':
					$template->addHeading($this->l10n->t('%1$s has tentatively accepted your invitation', [$sender]));
					break;
				case 'DECLINED':
					$template->addHeading($this->l10n->t('%1$s has declined your invitation', [$sender]));
					break;
				case null:
				default:
					$template->addHeading($this->l10n->t('%1$s has responded to your invitation', [$sender]));
					break;
			}
		} elseif ($method === IMipPlugin::METHOD_REQUEST && $isModified) {
			// TRANSLATORS Subject for email, when an invitation is updated. Ex: "Invitation updated: {{Event Name}}"
			$template->setSubject($this->l10n->t('Invitation updated: %1$s', [$summary]));
			$template->addHeading($this->l10n->t('%1$s updated the event "%2$s"', [$sender, $summary]));
		} else {
			// TRANSLATORS Subject for email, when an invitation is sent. Ex: "Invitation: {{Event Name}}"
			$template->setSubject($this->l10n->t('Invitation: %1$s', [$summary]));
			$template->addHeading($this->l10n->t('%1$s would like to invite you to "%2$s"', [$sender, $summary]));
		}
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function getAbsoluteImagePath($path): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath('core', $path)
		);
	}

	/**
	 * addAttendees: add organizer and attendee names/emails to iMip mail.
	 *
	 * Enable with DAV setting: invitation_list_attendees (default: no)
	 *
	 * The default is 'no', which matches old behavior, and is privacy preserving.
	 *
	 * To enable including attendees in invitation emails:
	 *   % php occ config:app:set dav invitation_list_attendees --value yes
	 *
	 * @param IEMailTemplate $template
	 * @param IL10N $this->l10n
	 * @param VEvent $vevent
	 * @author brad2014 on github.com
	 */
	public function addAttendees(IEMailTemplate $template, VEvent $vevent) {
		if ($this->config->getAppValue('dav', 'invitation_list_attendees', 'no') === 'no') {
			return;
		}

		if (isset($vevent->ORGANIZER)) {
			/** @var Property | Property\ICalendar\CalAddress $organizer */
			$organizer = $vevent->ORGANIZER;
			$organizerEmail = substr($organizer->getNormalizedValue(), 7);
			/** @var string|null $organizerName */
			$organizerName = isset($organizer->CN) ? $organizer->CN->getValue() : null;
			$organizerHTML = sprintf('<a href="%s">%s</a>',
				htmlspecialchars($organizer->getNormalizedValue()),
				htmlspecialchars($organizerName ?: $organizerEmail));
			$organizerText = sprintf('%s <%s>', $organizerName, $organizerEmail);
			if (isset($organizer['PARTSTAT'])) {
				/** @var Parameter $partstat */
				$partstat = $organizer['PARTSTAT'];
				if (strcasecmp($partstat->getValue(), 'ACCEPTED') === 0) {
					$organizerHTML .= ' ✔︎';
					$organizerText .= ' ✔︎';
				}
			}
			$template->addBodyListItem($organizerHTML, $this->l10n->t('Organizer:'),
				$this->getAbsoluteImagePath('caldav/organizer.png'),
				$organizerText, '', IMipPlugin::IMIP_INDENT);
		}

		$attendees = $vevent->select('ATTENDEE');
		if (count($attendees) === 0) {
			return;
		}

		$attendeesHTML = [];
		$attendeesText = [];
		foreach ($attendees as $attendee) {
			$attendeeEmail = substr($attendee->getNormalizedValue(), 7);
			$attendeeName = isset($attendee['CN']) ? $attendee['CN']->getValue() : null;
			$attendeeHTML = sprintf('<a href="%s">%s</a>',
				htmlspecialchars($attendee->getNormalizedValue()),
				htmlspecialchars($attendeeName ?: $attendeeEmail));
			$attendeeText = sprintf('%s <%s>', $attendeeName, $attendeeEmail);
			if (isset($attendee['PARTSTAT'])) {
				/** @var Parameter $partstat */
				$partstat = $attendee['PARTSTAT'];
				if (strcasecmp($partstat->getValue(), 'ACCEPTED') === 0) {
					$attendeeHTML .= ' ✔︎';
					$attendeeText .= ' ✔︎';
				}
			}
			$attendeesHTML[] = $attendeeHTML;
			$attendeesText[] = $attendeeText;
		}

		$template->addBodyListItem(implode('<br/>', $attendeesHTML), $this->l10n->t('Attendees:'),
			$this->getAbsoluteImagePath('caldav/attendees.png'),
			implode("\n", $attendeesText), '', IMipPlugin::IMIP_INDENT);
	}

	/**
	 * @param IEMailTemplate $template
	 * @param VEVENT $vevent
	 * @param $data
	 */
	public function addBulletList(IEMailTemplate $template, VEvent $vevent, $data) {
		$template->addBodyListItem(
			$data['meeting_title_html'] ?? $data['meeting_title'], $this->l10n->t('Title:'),
			$this->getAbsoluteImagePath('caldav/title.png'), $data['meeting_title'], '', IMipPlugin::IMIP_INDENT);
		if ($data['meeting_when'] !== '') {
			$template->addBodyListItem($data['meeting_when_html'] ?? $data['meeting_when'], $this->l10n->t('When:'),
				$this->getAbsoluteImagePath('caldav/time.png'), $data['meeting_when'], '', IMipPlugin::IMIP_INDENT);
		}
		if ($data['meeting_location'] !== '') {
			$template->addBodyListItem($data['meeting_location_html'] ?? $data['meeting_location'], $this->l10n->t('Location:'),
				$this->getAbsoluteImagePath('caldav/location.png'), $data['meeting_location'], '', IMipPlugin::IMIP_INDENT);
		}
		if ($data['meeting_url'] !== '') {
			$template->addBodyListItem($data['meeting_url_html'] ?? $data['meeting_url'], $this->l10n->t('Link:'),
				$this->getAbsoluteImagePath('caldav/link.png'), $data['meeting_url'], '', IMipPlugin::IMIP_INDENT);
		}
		if (isset($data['meeting_occurring'])) {
			$template->addBodyListItem($data['meeting_occurring_html'] ?? $data['meeting_occurring'], $this->l10n->t('Occurring:'),
				$this->getAbsoluteImagePath('caldav/time.png'), $data['meeting_occurring'], '', IMipPlugin::IMIP_INDENT);
		}

		$this->addAttendees($template, $vevent);

		/* Put description last, like an email body, since it can be arbitrarily long */
		if ($data['meeting_description']) {
			$template->addBodyListItem($data['meeting_description_html'] ?? $data['meeting_description'], $this->l10n->t('Description:'),
				$this->getAbsoluteImagePath('caldav/description.png'), $data['meeting_description'], '', IMipPlugin::IMIP_INDENT);
		}
	}

	/**
	 * @param Message $iTipMessage
	 * @return null|Property
	 */
	public function getCurrentAttendee(Message $iTipMessage): ?Property {
		/** @var VEvent $vevent */
		$vevent = $iTipMessage->message->VEVENT;
		$attendees = $vevent->select('ATTENDEE');
		foreach ($attendees as $attendee) {
			if ($iTipMessage->method === 'REPLY' && strcasecmp($attendee->getValue(), $iTipMessage->sender) === 0) {
				/** @var Property $attendee */
				return $attendee;
			} elseif (strcasecmp($attendee->getValue(), $iTipMessage->recipient) === 0) {
				/** @var Property $attendee */
				return $attendee;
			}
		}
		return null;
	}

	/**
	 * @param Message $iTipMessage
	 * @param VEvent $vevent
	 * @param int $lastOccurrence
	 * @return string
	 */
	public function createInvitationToken(Message $iTipMessage, VEvent $vevent, int $lastOccurrence): string {
		$token = $this->random->generate(60, ISecureRandom::CHAR_ALPHANUMERIC);

		$attendee = $iTipMessage->recipient;
		$organizer = $iTipMessage->sender;
		$sequence = $iTipMessage->sequence;
		$recurrenceId = isset($vevent->{'RECURRENCE-ID'}) ?
			$vevent->{'RECURRENCE-ID'}->serialize() : null;
		$uid = $vevent->{'UID'};

		$query = $this->db->getQueryBuilder();
		$query->insert('calendar_invitations')
			->values([
				'token' => $query->createNamedParameter($token),
				'attendee' => $query->createNamedParameter($attendee),
				'organizer' => $query->createNamedParameter($organizer),
				'sequence' => $query->createNamedParameter($sequence),
				'recurrenceid' => $query->createNamedParameter($recurrenceId),
				'expiration' => $query->createNamedParameter($lastOccurrence),
				'uid' => $query->createNamedParameter($uid)
			])
			->executeStatement();

		return $token;
	}

	/**
	 * @param IEMailTemplate $template
	 * @param $token
	 */
	public function addResponseButtons(IEMailTemplate $template, $token) {
		$template->addBodyButtonGroup(
			$this->l10n->t('Accept'),
			$this->urlGenerator->linkToRouteAbsolute('dav.invitation_response.accept', [
				'token' => $token,
			]),
			$this->l10n->t('Decline'),
			$this->urlGenerator->linkToRouteAbsolute('dav.invitation_response.decline', [
				'token' => $token,
			])
		);
	}

	public function addMoreOptionsButton(IEMailTemplate $template, $token) {
		$moreOptionsURL = $this->urlGenerator->linkToRouteAbsolute('dav.invitation_response.options', [
			'token' => $token,
		]);
		$html = vsprintf('<small><a href="%s">%s</a></small>', [
			$moreOptionsURL, $this->l10n->t('More options …')
		]);
		$text = $this->l10n->t('More options at %s', [$moreOptionsURL]);

		$template->addBodyText($html, $text);
	}

	public function getReplyingAttendee(Message $iTipMessage): ?Property {
		/** @var VEvent $vevent */
		$vevent = $iTipMessage->message->VEVENT;
		$attendees = $vevent->select('ATTENDEE');
		foreach ($attendees as $attendee) {
			/** @var Property $attendee */
			if (strcasecmp($attendee->getValue(), $iTipMessage->sender) === 0) {
				return $attendee;
			}
		}
		return null;
	}

	public function isRoomOrResource(Property $attendee): bool {
		$cuType = $attendee->offsetGet('CUTYPE');
		if (!$cuType instanceof Parameter) {
			return false;
		}
		$type = $cuType->getValue() ?? 'INDIVIDUAL';
		if (\in_array(strtoupper($type), ['RESOURCE', 'ROOM'], true)) {
			// Don't send emails to things
			return true;
		}
		return false;
	}

	public function isCircle(Property $attendee): bool {
		$cuType = $attendee->offsetGet('CUTYPE');
		if (!$cuType instanceof Parameter) {
			return false;
		}

		$uri = $attendee->getValue();
		if (!$uri) {
			return false;
		}

		$cuTypeValue = $cuType->getValue();
		return $cuTypeValue === 'GROUP' && str_starts_with($uri, 'mailto:circle+');
	}

	public function minimizeInterval(\DateInterval $dateInterval): array {
		// evaluate if time interval is in the past
		if ($dateInterval->invert == 1) {
			return ['interval' => 1, 'scale' => 'past'];
		}
		// evaluate interval parts and return smallest time period
		if ($dateInterval->y > 0) {
			$interval = $dateInterval->y;
			$scale = 'year';
		} elseif ($dateInterval->m > 0) {
			$interval = $dateInterval->m;
			$scale = 'month';
		} elseif ($dateInterval->d >= 7) {
			$interval = (int)($dateInterval->d / 7);
			$scale = 'week';
		} elseif ($dateInterval->d > 0) {
			$interval = $dateInterval->d;
			$scale = 'day';
		} elseif ($dateInterval->h > 0) {
			$interval = $dateInterval->h;
			$scale = 'hour';
		} else {
			$interval = $dateInterval->i;
			$scale = 'minute';
		}

		return ['interval' => $interval, 'scale' => $scale];
	}

	/**
	 * Localizes week day names to another language
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function localizeDayName(string $value): string {
		return match ($value) {
			'Monday' => $this->l10n->t('Monday'),
			'Tuesday' => $this->l10n->t('Tuesday'),
			'Wednesday' => $this->l10n->t('Wednesday'),
			'Thursday' => $this->l10n->t('Thursday'),
			'Friday' => $this->l10n->t('Friday'),
			'Saturday' => $this->l10n->t('Saturday'),
			'Sunday' => $this->l10n->t('Sunday'),
		};
	}

	/**
	 * Localizes month names to another language
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function localizeMonthName(string $value): string {
		return match ($value) {
			'January' => $this->l10n->t('January'),
			'February' => $this->l10n->t('February'),
			'March' => $this->l10n->t('March'),
			'April' => $this->l10n->t('April'),
			'May' => $this->l10n->t('May'),
			'June' => $this->l10n->t('June'),
			'July' => $this->l10n->t('July'),
			'August' => $this->l10n->t('August'),
			'September' => $this->l10n->t('September'),
			'October' => $this->l10n->t('October'),
			'November' => $this->l10n->t('November'),
			'December' => $this->l10n->t('December'),
		};
	}

	/**
	 * Localizes relative position names to another language
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function localizeRelativePositionName(string $value): string {
		return match ($value) {
			'First' => $this->l10n->t('First'),
			'Second' => $this->l10n->t('Second'),
			'Third' => $this->l10n->t('Third'),
			'Fourth' => $this->l10n->t('Fourth'),
			'Fifth' => $this->l10n->t('Fifth'),
			'Last' => $this->l10n->t('Last'),
			'Second Last' => $this->l10n->t('Second Last'),
			'Third Last' => $this->l10n->t('Third Last'),
			'Fourth Last' => $this->l10n->t('Fourth Last'),
			'Fifth Last' => $this->l10n->t('Fifth Last'),
		};
	}
}
