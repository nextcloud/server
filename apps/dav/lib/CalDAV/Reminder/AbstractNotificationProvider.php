<?php
/**
 * @copyright Copyright (c) 2018 Thomas Citharel <tcit@tcit.fr>
 *
 * @author Thomas Citharel <tcit@tcit.fr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\CalDAV\Reminder;

use \DateTime;
use \DateTimeImmutable;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\L10N\IFactory as L10NFactory;
use OCP\IUser;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\Parameter;
use Sabre\VObject\Property;

abstract class AbstractNotificationProvider
{

	public const NOTIFICATION_TYPE = '';

    /** @var ILogger */
    protected $logger;

    /** @var L10NFactory */
    protected $l10nFactory;

    /** @var IL10N */
    protected $l10n;

    /** @var IURLGenerator */
    protected $urlGenerator;

    /** @var IConfig */
	protected $config;

	/**
	 * @param ILogger $logger
	 * @param L10NFactory $l10nFactory
	 * @param IConfig $config
	 * @param IUrlGenerator $urlGenerator
	 */
	public function __construct(ILogger $logger, L10NFactory $l10nFactory, IURLGenerator $urlGenerator, IConfig $config) {
		$this->logger = $logger;
		$this->l10nFactory = $l10nFactory;
        $this->urlGenerator = $urlGenerator;
		$this->config = $config;
    }

	/**
	 * Send notification
	 *
	 * @param VCalendar $vcalendar
	 * @param string $calendarDisplayName
	 * @param IUser $user
	 * @return void
	 */
    public function send(VCalendar $vcalendar, string $calendarDisplayName, IUser $user): void {}

	/**
	 * @var VCalendar $vcalendar
	 * @var string $defaultValue
	 * @return array
	 * @throws \Exception
	 */
    protected function extractEventDetails(VCalendar $vcalendar, $defaultValue = ''): array
	{
		/** @var VEvent $vevent */
        $vevent = $vcalendar->VEVENT;

        /** @var Property $start */
		$start = $vevent->DTSTART;
		if (isset($vevent->DTEND)) {
			$end = $vevent->DTEND;
		} elseif (isset($vevent->DURATION)) {
			$isFloating = $vevent->DTSTART->isFloating();
			$end = clone $vevent->DTSTART;
			$endDateTime = $end->getDateTime();
			$endDateTime = $endDateTime->add(DateTimeParser::parse($vevent->DURATION->getValue()));
			$end->setDateTime($endDateTime, $isFloating);
		} elseif (!$vevent->DTSTART->hasTime()) {
			$isFloating = $vevent->DTSTART->isFloating();
			$end = clone $vevent->DTSTART;
			$endDateTime = $end->getDateTime();
			$endDateTime = $endDateTime->modify('+1 day');
			$end->setDateTime($endDateTime, $isFloating);
		} else {
			$end = clone $vevent->DTSTART;
		}

        return [
            'title' => (string) $vevent->SUMMARY ?: $defaultValue,
            'description' => (string) $vevent->DESCRIPTION ?: $defaultValue,
            'start'=> $start->getDateTime(),
            'end' => $end->getDateTime(),
            'when' => $this->generateWhenString($start, $end),
            'url' => (string) $vevent->URL ?: $defaultValue,
            'location' => (string) $vevent->LOCATION ?: $defaultValue,
            'uid' => (string) $vevent->UID,
        ];
    }

	/**
	 * @param Property $dtstart
	 * @param Property $dtend
	 * @return string
	 * @throws \Exception
	 */
	private function generateWhenString(Property $dtstart, Property $dtend): string
	{
		$isAllDay = $dtstart instanceof Property\ICalendar\Date;

		/** @var Property\ICalendar\Date | Property\ICalendar\DateTime $dtstart */
		/** @var Property\ICalendar\Date | Property\ICalendar\DateTime $dtend */
		/** @var DateTimeImmutable $dtstartDt */
		$dtstartDt = $dtstart->getDateTime();
		/** @var DateTimeImmutable $dtendDt */
		$dtendDt = $dtend->getDateTime();

		$diff = $dtstartDt->diff($dtendDt);

		$dtstartDt = new DateTime($dtstartDt->format(DateTime::ATOM));
		$dtendDt = new DateTime($dtendDt->format(DateTime::ATOM));

		if ($isAllDay) {
			// One day event
			if ($diff->days === 1) {
				return $this->l10n->l('date', $dtstartDt, ['width' => 'medium']);
			}

			//event that spans over multiple days
			$localeStart = $this->l10n->l('date', $dtstartDt, ['width' => 'medium']);
			$localeEnd = $this->l10n->l('date', $dtendDt, ['width' => 'medium']);

			return $localeStart . ' - ' . $localeEnd;
		}

		/** @var Property\ICalendar\DateTime $dtstart */
		/** @var Property\ICalendar\DateTime $dtend */
		$isFloating = $dtstart->isFloating();
		$startTimezone = $endTimezone = null;
		if (!$isFloating) {
			$prop = $dtstart->offsetGet('TZID');
			if ($prop instanceof Parameter) {
				$startTimezone = $prop->getValue();
			}

			$prop = $dtend->offsetGet('TZID');
			if ($prop instanceof Parameter) {
				$endTimezone = $prop->getValue();
			}
		}

		$localeStart = $this->l10n->l('weekdayName', $dtstartDt, ['width' => 'abbreviated']) . ', ' .
			$this->l10n->l('datetime', $dtstartDt, ['width' => 'medium|short']);

		// always show full date with timezone if timezones are different
		if ($startTimezone !== $endTimezone) {
			$localeEnd = $this->l10n->l('datetime', $dtendDt, ['width' => 'medium|short']);

			return $localeStart . ' (' . $startTimezone . ') - ' .
				$localeEnd . ' (' . $endTimezone . ')';
		}

		// show only end time if date is the same
		if ($this->isDayEqual($dtstartDt, $dtendDt)) {
			$localeEnd = $this->l10n->l('time', $dtendDt, ['width' => 'short']);
		} else {
			$localeEnd = $this->l10n->l('weekdayName', $dtendDt, ['width' => 'abbreviated']) . ', ' .
				$this->l10n->l('datetime', $dtendDt, ['width' => 'medium|short']);
		}

		return  $localeStart . ' - ' . $localeEnd . ' (' . $startTimezone . ')';
	}

	/**
	 * @param DateTime $dtStart
	 * @param DateTime $dtEnd
	 * @return bool
	 */
    private function isDayEqual(DateTime $dtStart, DateTime $dtEnd): bool
    {
		return $dtStart->format('Y-m-d') === $dtEnd->format('Y-m-d');
	}
}
