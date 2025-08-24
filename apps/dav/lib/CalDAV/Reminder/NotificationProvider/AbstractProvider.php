<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Reminder\NotificationProvider;

use OCA\DAV\CalDAV\Reminder\INotificationProvider;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory as L10NFactory;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\Property;

/**
 * Class AbstractProvider
 *
 * @package OCA\DAV\CalDAV\Reminder\NotificationProvider
 */
abstract class AbstractProvider implements INotificationProvider {

	/** @var string */
	public const NOTIFICATION_TYPE = '';

	/** @var IL10N[] */
	private $l10ns;

	/** @var string */
	private $fallbackLanguage;

	public function __construct(
		protected LoggerInterface $logger,
		protected L10NFactory $l10nFactory,
		protected IURLGenerator $urlGenerator,
		protected IConfig $config,
	) {
	}

	/**
	 * Send notification
	 *
	 * @param VEvent $vevent
	 * @param string|null $calendarDisplayName
	 * @param string[] $principalEmailAddresses
	 * @param IUser[] $users
	 * @return void
	 */
	abstract public function send(VEvent $vevent,
		?string $calendarDisplayName,
		array $principalEmailAddresses,
		array $users = []): void;

	/**
	 * @return string
	 */
	protected function getFallbackLanguage():string {
		if ($this->fallbackLanguage) {
			return $this->fallbackLanguage;
		}

		$fallbackLanguage = $this->l10nFactory->findGenericLanguage();
		$this->fallbackLanguage = $fallbackLanguage;

		return $fallbackLanguage;
	}

	/**
	 * @param string $lang
	 * @return bool
	 */
	protected function hasL10NForLang(string $lang):bool {
		return $this->l10nFactory->languageExists('dav', $lang);
	}

	/**
	 * @param string $lang
	 * @return IL10N
	 */
	protected function getL10NForLang(string $lang):IL10N {
		if (isset($this->l10ns[$lang])) {
			return $this->l10ns[$lang];
		}

		$l10n = $this->l10nFactory->get('dav', $lang);
		$this->l10ns[$lang] = $l10n;

		return $l10n;
	}

	/**
	 * @param VEvent $vevent
	 * @return string
	 */
	private function getStatusOfEvent(VEvent $vevent):string {
		if ($vevent->STATUS) {
			return (string)$vevent->STATUS;
		}

		// Doesn't say so in the standard,
		// but we consider events without a status
		// to be confirmed
		return 'CONFIRMED';
	}

	/**
	 * @param VEvent $vevent
	 * @return bool
	 */
	protected function isEventTentative(VEvent $vevent):bool {
		return $this->getStatusOfEvent($vevent) === 'TENTATIVE';
	}

	/**
	 * @param VEvent $vevent
	 * @return Property\ICalendar\DateTime
	 */
	protected function getDTEndFromEvent(VEvent $vevent):Property\ICalendar\DateTime {
		if (isset($vevent->DTEND)) {
			return $vevent->DTEND;
		}

		if (isset($vevent->DURATION)) {
			$isFloating = $vevent->DTSTART->isFloating();
			/** @var Property\ICalendar\DateTime $end */
			$end = clone $vevent->DTSTART;
			$endDateTime = $end->getDateTime();
			$endDateTime = $endDateTime->add(DateTimeParser::parse($vevent->DURATION->getValue()));
			$end->setDateTime($endDateTime, $isFloating);

			return $end;
		}

		if (!$vevent->DTSTART->hasTime()) {
			$isFloating = $vevent->DTSTART->isFloating();
			/** @var Property\ICalendar\DateTime $end */
			$end = clone $vevent->DTSTART;
			$endDateTime = $end->getDateTime();
			$endDateTime = $endDateTime->modify('+1 day');
			$end->setDateTime($endDateTime, $isFloating);

			return $end;
		}

		return clone $vevent->DTSTART;
	}

	protected function getCalendarDisplayNameFallback(string $lang): string {
		return $this->getL10NForLang($lang)->t('Untitled calendar');
	}
}
