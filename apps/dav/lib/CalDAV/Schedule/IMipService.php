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
use OCP\Config\IUserConfig;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUserManager;
use OCP\L10N\IFactory as L10NFactory;
use OCP\Mail\EMailDetails;
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

	private const int MAX_LISTED_ATTENDEES = 5;

	private IL10N $l10n;

	/** @var string[] */
	private const array STRING_DIFF = [
		'meeting_title' => 'SUMMARY',
		'meeting_description' => 'DESCRIPTION',
		'meeting_url' => 'URL',
		'meeting_location' => 'LOCATION'
	];

	public function __construct(
		private URLGenerator $urlGenerator,
		private IDBConnection $db,
		private ISecureRandom $random,
		private L10NFactory $l10nFactory,
		private ITimeFactory $timeFactory,
		private readonly IUserManager $userManager,
		private readonly IUserConfig $userConfig,
		private readonly IAppConfig $appConfig,
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

	/**
	 * Language of the attendee the email is written for
	 */
	public function getLanguageCode(): string {
		return $this->l10n->getLanguageCode();
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

	private function isLinkUrl(string $url): bool {
		return filter_var($url, FILTER_VALIDATE_URL) !== false
			&& (str_starts_with($url, 'http://') || str_starts_with($url, 'https://'));
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

		if (!empty($oldVEvent)) {
			foreach (self::STRING_DIFF as $key => $property) {
				$oldValue = self::readPropertyWithDefault($oldVEvent, $property, $defaultVal);
				if ($oldValue !== $defaultVal && $oldValue !== $data[$key]) {
					$data[$key . '_previous'] = $oldValue;
				}
			}

			$oldMeetingWhen = $this->generateWhenString($eventReaderPrevious);
			if ($oldMeetingWhen !== $data['meeting_when']) {
				$data['meeting_when_previous'] = $oldMeetingWhen;
			}
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
		// In 1 minute/hour/day/week/month/year on July 1, 2024 for the entire day
		// In 1 minute/hour/day/week/month/year on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)
		// In 2 minutes/hours/days/weeks/months/years on July 1, 2024 for the entire day
		// In 2 minutes/hours/days/weeks/months/years on July 1, 2024 between 8:00 AM - 9:00 AM (America/Toronto)
		return match ([$occurring['scale'], $endTime !== null]) {
			['past', false] => $this->l10n->t(
				'In the past on %1$s for the entire day',
				[$startDate]
			),
			['minute', false] => $this->l10n->n(
				'In %n minute on %1$s for the entire day',
				'In %n minutes on %1$s for the entire day',
				$occurring['interval'],
				[$startDate]
			),
			['hour', false] => $this->l10n->n(
				'In %n hour on %1$s for the entire day',
				'In %n hours on %1$s for the entire day',
				$occurring['interval'],
				[$startDate]
			),
			['day', false] => $this->l10n->n(
				'In %n day on %1$s for the entire day',
				'In %n days on %1$s for the entire day',
				$occurring['interval'],
				[$startDate]
			),
			['week', false] => $this->l10n->n(
				'In %n week on %1$s for the entire day',
				'In %n weeks on %1$s for the entire day',
				$occurring['interval'],
				[$startDate]
			),
			['month', false] => $this->l10n->n(
				'In %n month on %1$s for the entire day',
				'In %n months on %1$s for the entire day',
				$occurring['interval'],
				[$startDate]
			),
			['year', false] => $this->l10n->n(
				'In %n year on %1$s for the entire day',
				'In %n years on %1$s for the entire day',
				$occurring['interval'],
				[$startDate]
			),
			['past', true] => $this->l10n->t(
				'In the past on %1$s between %2$s - %3$s',
				[$startDate, $startTime, $endTime]
			),
			['minute', true] => $this->l10n->n(
				'In %n minute on %1$s between %2$s - %3$s',
				'In %n minutes on %1$s between %2$s - %3$s',
				$occurring['interval'],
				[$startDate, $startTime, $endTime]
			),
			['hour', true] => $this->l10n->n(
				'In %n hour on %1$s between %2$s - %3$s',
				'In %n hours on %1$s between %2$s - %3$s',
				$occurring['interval'],
				[$startDate, $startTime, $endTime]
			),
			['day', true] => $this->l10n->n(
				'In %n day on %1$s between %2$s - %3$s',
				'In %n days on %1$s between %2$s - %3$s',
				$occurring['interval'],
				[$startDate, $startTime, $endTime]
			),
			['week', true] => $this->l10n->n(
				'In %n week on %1$s between %2$s - %3$s',
				'In %n weeks on %1$s between %2$s - %3$s',
				$occurring['interval'],
				[$startDate, $startTime, $endTime]
			),
			['month', true] => $this->l10n->n(
				'In %n month on %1$s between %2$s - %3$s',
				'In %n months on %1$s between %2$s - %3$s',
				$occurring['interval'],
				[$startDate, $startTime, $endTime]
			),
			['year', true] => $this->l10n->n(
				'In %n year on %1$s between %2$s - %3$s',
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
		$days = implode(', ', array_map(function ($value) {
			return $this->localizeDayName($value);
		}, $er->recurringDaysOfWeekNamed()));
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
			$days = implode(', ', array_map(function ($value) {
				return $this->localizeRelativePositionName($value);
			}, $er->recurringRelativePositionNamed())) . ' '
				. implode(', ', array_map(function ($value) {
					return $this->localizeDayName($value);
				}, $er->recurringDaysOfWeekNamed()));
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
		$months = implode(', ', array_map(function ($value) {
			return $this->localizeMonthName($value);
		}, $er->recurringMonthsOfYearNamed()));
		// days of month
		if ($er->recurringPattern() === 'R') {
			$days = implode(', ', array_map(function ($value) {
				return $this->localizeRelativePositionName($value);
			}, $er->recurringRelativePositionNamed())) . ' '
				. implode(', ', array_map(function ($value) {
					return $this->localizeDayName($value);
				}, $er->recurringDaysOfWeekNamed()));
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
		// In 1 minute/hour/day/week/month/year on July 1, 2024
		// In 1 minute/hour/day/week/month/year on July 1, 2024 then on July 3, 2024
		// In 1 minute/hour/day/week/month/year on July 1, 2024 then on July 3, 2024 and July 5, 2024
		// In 2 minutes/hours/days/weeks/months/years on July 1, 2024
		// In 2 minutes/hours/days/weeks/months/years on July 1, 2024 then on July 3, 2024
		// In 2 minutes/hours/days/weeks/months/years on July 1, 2024 then on July 3, 2024 and July 5, 2024
		return match ([$occurrenceIn['scale'], $occurrence2 !== null, $occurrence3 !== null]) {
			['past', false, false] => $this->l10n->t(
				'In the past on %1$s',
				[$occurrence]
			),
			['minute', false, false] => $this->l10n->n(
				'In %n minute on %1$s',
				'In %n minutes on %1$s',
				$occurrenceIn['interval'],
				[$occurrence]
			),
			['hour', false, false] => $this->l10n->n(
				'In %n hour on %1$s',
				'In %n hours on %1$s',
				$occurrenceIn['interval'],
				[$occurrence]
			),
			['day', false, false] => $this->l10n->n(
				'In %n day on %1$s',
				'In %n days on %1$s',
				$occurrenceIn['interval'],
				[$occurrence]
			),
			['week', false, false] => $this->l10n->n(
				'In %n week on %1$s',
				'In %n weeks on %1$s',
				$occurrenceIn['interval'],
				[$occurrence]
			),
			['month', false, false] => $this->l10n->n(
				'In %n month on %1$s',
				'In %n months on %1$s',
				$occurrenceIn['interval'],
				[$occurrence]
			),
			['year', false, false] => $this->l10n->n(
				'In %n year on %1$s',
				'In %n years on %1$s',
				$occurrenceIn['interval'],
				[$occurrence]
			),
			['past', true, false] => $this->l10n->t(
				'In the past on %1$s then on %2$s',
				[$occurrence, $occurrence2]
			),
			['minute', true, false] => $this->l10n->n(
				'In %n minute on %1$s then on %2$s',
				'In %n minutes on %1$s then on %2$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2]
			),
			['hour', true, false] => $this->l10n->n(
				'In %n hour on %1$s then on %2$s',
				'In %n hours on %1$s then on %2$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2]
			),
			['day', true, false] => $this->l10n->n(
				'In %n day on %1$s then on %2$s',
				'In %n days on %1$s then on %2$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2]
			),
			['week', true, false] => $this->l10n->n(
				'In %n week on %1$s then on %2$s',
				'In %n weeks on %1$s then on %2$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2]
			),
			['month', true, false] => $this->l10n->n(
				'In %n month on %1$s then on %2$s',
				'In %n months on %1$s then on %2$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2]
			),
			['year', true, false] => $this->l10n->n(
				'In %n year on %1$s then on %2$s',
				'In %n years on %1$s then on %2$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2]
			),
			['past', true, true] => $this->l10n->t(
				'In the past on %1$s then on %2$s and %3$s',
				[$occurrence, $occurrence2, $occurrence3]
			),
			['minute', true, true] => $this->l10n->n(
				'In %n minute on %1$s then on %2$s and %3$s',
				'In %n minutes on %1$s then on %2$s and %3$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2, $occurrence3]
			),
			['hour', true, true] => $this->l10n->n(
				'In %n hour on %1$s then on %2$s and %3$s',
				'In %n hours on %1$s then on %2$s and %3$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2, $occurrence3]
			),
			['day', true, true] => $this->l10n->n(
				'In %n day on %1$s then on %2$s and %3$s',
				'In %n days on %1$s then on %2$s and %3$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2, $occurrence3]
			),
			['week', true, true] => $this->l10n->n(
				'In %n week on %1$s then on %2$s and %3$s',
				'In %n weeks on %1$s then on %2$s and %3$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2, $occurrence3]
			),
			['month', true, true] => $this->l10n->n(
				'In %n month on %1$s then on %2$s and %3$s',
				'In %n months on %1$s then on %2$s and %3$s',
				$occurrenceIn['interval'],
				[$occurrence, $occurrence2, $occurrence3]
			),
			['year', true, true] => $this->l10n->n(
				'In %n year on %1$s then on %2$s and %3$s',
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

		$data = [];
		$data['meeting_when'] = $this->generateWhenString($eventReaderCurrent);
		$data['meeting_title'] = isset($vEvent->SUMMARY) && (string)$vEvent->SUMMARY !== '' ? (string)$vEvent->SUMMARY : $this->l10n->t('Untitled event');
		$data['meeting_description'] = isset($vEvent->DESCRIPTION) ? (string)$vEvent->DESCRIPTION : $defaultVal;
		$data['meeting_url'] = isset($vEvent->URL) ? (string)$vEvent->URL : $defaultVal;
		$data['meeting_location'] = isset($vEvent->LOCATION) ? (string)$vEvent->LOCATION : $defaultVal;

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
	 * Check if an email address belongs to a system user
	 *
	 * @param string $email
	 * @return bool True if the email belongs to a system user, false otherwise
	 */
	public function isSystemUser(string $email): bool {
		return !empty($this->userManager->getByEmail($email));
	}

	/**
	 * @param Property $attendee
	 */
	public function setL10nFromAttendee(Property $attendee) {
		$language = null;
		$locale = null;
		// check if the attendee is a system user
		$userAddress = $attendee->getValue();
		if (str_starts_with($userAddress, 'mailto:')) {
			$userAddress = substr($userAddress, 7);
		}
		$users = $this->userManager->getByEmail($userAddress);
		if ($users !== []) {
			$user = array_shift($users);
			$language = $this->userConfig->getValueString($user->getUID(), 'core', 'lang', '') ?: null;
			$locale = $this->userConfig->getValueString($user->getUID(), 'core', 'locale', '') ?: null;
		}
		// fallback to attendee LANGUAGE parameter if language not set
		if ($language === null && isset($attendee['LANGUAGE']) && $attendee['LANGUAGE'] instanceof Parameter) {
			$language = $attendee['LANGUAGE']->getValue();
		}
		// fallback to system language if language not set
		if ($language === null) {
			$language = $this->l10nFactory->findGenericLanguage();
		}
		// fallback to system locale if locale not set
		if ($locale === null) {
			$locale = $this->l10nFactory->findLocale($language);
		}
		$this->l10n = $this->l10nFactory->get('dav', $language, $locale);
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
	 * Add the event card, followed by the description, to the iMip mail.
	 *
	 * Values that changed in an update are shown with their previous value.
	 */
	public function addEventDetails(IEMailTemplate $template, VEvent $vevent, array $data, string $calendarName = ''): void {
		$start = (new EventReader($vevent))->startDateTime();
		$details = (new EMailDetails($data['meeting_title'] ?: $this->l10n->t('Untitled event')))
			->setSubtitle((string)$this->l10n->l('date', $start, ['width' => 'full']))
			->setDateBadge(
				(string)$this->l10n->l('date', $start, ['width' => '~MMM']),
				(string)$this->l10n->l('date', $start, ['width' => '~d']),
			);

		if (isset($data['meeting_title_previous'])) {
			$this->addDetailsRow($details, $this->l10n->t('Title'), $data, 'meeting_title');
		}
		$this->addDetailsRow($details, $this->l10n->t('When'), $data, 'meeting_when');
		if (isset($data['meeting_occurring'])) {
			$details->addRow($this->l10n->t('Occurring'))->text($data['meeting_occurring']);
		}
		$this->addDetailsRow($details, $this->l10n->t('Where'), $data, 'meeting_location');
		$this->addDetailsRow($details, $this->l10n->t('Link'), $data, 'meeting_url');
		if ($calendarName !== '') {
			$details->addRow($this->l10n->t('Calendar'))->text($calendarName);
		}
		$this->addAttendees($details, $vevent);

		$template->addBodyDetails($details);

		if ($data['meeting_description'] !== '') {
			$template->addBodyNote($data['meeting_description'], $this->l10n->t('Description'));
			if (isset($data['meeting_description_previous'])) {
				$template->addBodyNote($data['meeting_description_previous'], $this->l10n->t('Previous description'));
			}
		}
	}

	private function addDetailsRow(EMailDetails $details, string $label, array $data, string $key): void {
		$value = $data[$key] ?? '';
		if ($value === '') {
			return;
		}

		$row = $details->addRow($label);
		if ($this->isLinkUrl($value)) {
			$row->link($value, $value);
		} else {
			$row->text($value);
		}
		if (isset($data[$key . '_previous'])) {
			$row->muted($this->l10n->t('Previously: %s', [$data[$key . '_previous']]));
		}
	}

	/**
	 * Add organizer and attendee rows to the event card.
	 *
	 * Enable with DAV setting: invitation_list_attendees (default: no)
	 *
	 * The default is 'no', which matches old behavior, and is privacy preserving.
	 *
	 * To enable including attendees in invitation emails:
	 *   % php occ config:app:set dav invitation_list_attendees --value yes --type bool
	 *
	 * @author brad2014 on github.com
	 */
	private function addAttendees(EMailDetails $details, VEvent $vevent): void {
		if (!$this->appConfig->getValueBool('dav', 'invitation_list_attendees')) {
			return;
		}

		if (isset($vevent->ORGANIZER)) {
			/** @var Property&Property\ICalendar\CalAddress $organizer */
			$organizer = $vevent->ORGANIZER;
			$details->addRow($this->l10n->t('Organizer'))
				->link($this->getAttendeeLabel($organizer), $organizer->getNormalizedValue());
		}

		$attendees = $vevent->select('ATTENDEE');
		if (count($attendees) === 0) {
			return;
		}

		$row = $details->addRow($this->l10n->t('Attendees'));
		foreach (array_slice($attendees, 0, self::MAX_LISTED_ATTENDEES) as $attendee) {
			/** @var Property&Property\ICalendar\CalAddress $attendee */
			$row->link($this->getAttendeeLabel($attendee), $attendee->getNormalizedValue());
		}
		$hiddenCount = count($attendees) - self::MAX_LISTED_ATTENDEES;
		if ($hiddenCount > 0) {
			$row->muted($this->l10n->n('and %n other', 'and %n others', $hiddenCount));
		}
	}

	/**
	 * @param Property&Property\ICalendar\CalAddress $attendee
	 */
	private function getAttendeeLabel(Property $attendee): string {
		$cn = $attendee['CN'];
		$label = $cn instanceof Parameter ? (string)$cn->getValue() : '';
		if ($label === '') {
			$label = substr($attendee->getNormalizedValue(), 7);
		}
		$partstat = $attendee['PARTSTAT'];
		if ($partstat instanceof Parameter && strcasecmp((string)$partstat->getValue(), 'ACCEPTED') === 0) {
			$label .= ' ✔︎';
		}
		return $label;
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
		$recurrenceId = isset($vevent->{'RECURRENCE-ID'})
			? $vevent->{'RECURRENCE-ID'}->serialize() : null;
		$uid = $vevent->{'UID'}?->getValue();

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
		$template->addBodyButtons([
			[
				'text' => $this->l10n->t('Accept'),
				'url' => $this->urlGenerator->linkToRouteAbsolute('dav.invitation_response.accept', ['token' => $token]),
			],
			[
				'text' => $this->l10n->t('Decline'),
				'url' => $this->urlGenerator->linkToRouteAbsolute('dav.invitation_response.decline', ['token' => $token]),
			],
			[
				'text' => $this->l10n->t('More options …'),
				'url' => $this->urlGenerator->linkToRouteAbsolute('dav.invitation_response.options', ['token' => $token]),
			],
		], $this->l10n->t('Will you attend?'));
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
		if ($dateInterval->invert === 1) {
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
