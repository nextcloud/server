<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2019, Georg Ehrke
 *
 * @author Achim Königs <garfonso@tratschtante.de>
 * @author Christian Weiske <cweiske@cweiske.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Sven Strickroth <email@cs-ware.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Valdnet <47037905+Valdnet@users.noreply.github.com>
 * @author Cédric Neukom <github@webguy.ch>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\CalDAV;

use Exception;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\DAV\GroupPrincipalBackend;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\Document;
use Sabre\VObject\InvalidDataException;
use Sabre\VObject\Property\VCard\DateAndOrTime;
use Sabre\VObject\Reader;

/**
 * Class BirthdayService
 *
 * @package OCA\DAV\CalDAV
 */
class BirthdayService {
	public const BIRTHDAY_CALENDAR_URI = 'contact_birthdays';
	public const EXCLUDE_FROM_BIRTHDAY_CALENDAR_PROPERTY_NAME = 'X-NC-EXCLUDE-FROM-BIRTHDAY-CALENDAR';

	private GroupPrincipalBackend $principalBackend;
	private CalDavBackend $calDavBackEnd;
	private CardDavBackend $cardDavBackEnd;
	private IConfig $config;
	private IDBConnection $dbConnection;
	private IL10N $l10n;

	/**
	 * BirthdayService constructor.
	 */
	public function __construct(CalDavBackend $calDavBackEnd,
								CardDavBackend $cardDavBackEnd,
								GroupPrincipalBackend $principalBackend,
								IConfig $config,
								IDBConnection $dbConnection,
								IL10N $l10n) {
		$this->calDavBackEnd = $calDavBackEnd;
		$this->cardDavBackEnd = $cardDavBackEnd;
		$this->principalBackend = $principalBackend;
		$this->config = $config;
		$this->dbConnection = $dbConnection;
		$this->l10n = $l10n;
	}

	public function onCardChanged(int $addressBookId,
								  string $cardUri,
								  string $cardData): void {
		if (!$this->isGloballyEnabled()) {
			return;
		}

		$targetPrincipals = $this->getAllAffectedPrincipals($addressBookId);
		$book = $this->cardDavBackEnd->getAddressBookById($addressBookId);
		if ($book === null) {
			return;
		}
		$targetPrincipals[] = $book['principaluri'];
		$datesToSync = [
			['postfix' => '', 'field' => 'BDAY'],
			['postfix' => '-death', 'field' => 'DEATHDATE'],
			['postfix' => '-anniversary', 'field' => 'ANNIVERSARY'],
		];

		foreach ($targetPrincipals as $principalUri) {
			if (!$this->isUserEnabled($principalUri)) {
				continue;
			}

			$reminderOffset = $this->getReminderOffsetForUser($principalUri);

			$calendar = $this->ensureCalendarExists($principalUri);
			if ($calendar === null) {
				return;
			}
			foreach ($datesToSync as $type) {
				$this->updateCalendar($cardUri, $cardData, $book, (int) $calendar['id'], $type, $reminderOffset);
			}
		}
	}

	public function onCardDeleted(int $addressBookId,
								  string $cardUri): void {
		if (!$this->isGloballyEnabled()) {
			return;
		}

		$targetPrincipals = $this->getAllAffectedPrincipals($addressBookId);
		$book = $this->cardDavBackEnd->getAddressBookById($addressBookId);
		$targetPrincipals[] = $book['principaluri'];
		foreach ($targetPrincipals as $principalUri) {
			if (!$this->isUserEnabled($principalUri)) {
				continue;
			}

			$calendar = $this->ensureCalendarExists($principalUri);
			foreach (['', '-death', '-anniversary'] as $tag) {
				$objectUri = $book['uri'] . '-' . $cardUri . $tag .'.ics';
				$this->calDavBackEnd->deleteCalendarObject($calendar['id'], $objectUri, CalDavBackend::CALENDAR_TYPE_CALENDAR, true);
			}
		}
	}

	/**
	 * @throws \Sabre\DAV\Exception\BadRequest
	 */
	public function ensureCalendarExists(string $principal): ?array {
		$calendar = $this->calDavBackEnd->getCalendarByUri($principal, self::BIRTHDAY_CALENDAR_URI);
		if (!is_null($calendar)) {
			return $calendar;
		}
		$this->calDavBackEnd->createCalendar($principal, self::BIRTHDAY_CALENDAR_URI, [
			'{DAV:}displayname' => $this->l10n->t('Contact birthdays'),
			'{http://apple.com/ns/ical/}calendar-color' => '#E9D859',
			'components' => 'VEVENT',
		]);

		return $this->calDavBackEnd->getCalendarByUri($principal, self::BIRTHDAY_CALENDAR_URI);
	}

	/**
	 * @param $cardData
	 * @param $dateField
	 * @param $postfix
	 * @param $reminderOffset
	 * @return VCalendar|null
	 * @throws InvalidDataException
	 */
	public function buildDateFromContact(string  $cardData,
										 string  $dateField,
										 string  $postfix,
										 ?string $reminderOffset):?VCalendar {
		if (empty($cardData)) {
			return null;
		}
		try {
			$doc = Reader::read($cardData);
			// We're always converting to vCard 4.0 so we can rely on the
			// VCardConverter handling the X-APPLE-OMIT-YEAR property for us.
			if (!$doc instanceof VCard) {
				return null;
			}
			$doc = $doc->convert(Document::VCARD40);
		} catch (Exception $e) {
			return null;
		}

		if (isset($doc->{self::EXCLUDE_FROM_BIRTHDAY_CALENDAR_PROPERTY_NAME})) {
			return null;
		}

		if (!isset($doc->{$dateField})) {
			return null;
		}
		if (!isset($doc->FN)) {
			return null;
		}
		$birthday = $doc->{$dateField};
		if (!(string)$birthday) {
			return null;
		}
		// Skip if the BDAY property is not of the right type.
		if (!$birthday instanceof DateAndOrTime) {
			return null;
		}

		// Skip if we can't parse the BDAY value.
		try {
			$dateParts = DateTimeParser::parseVCardDateTime($birthday->getValue());
		} catch (InvalidDataException $e) {
			return null;
		}
		if ($dateParts['year'] !== null) {
			$parameters = $birthday->parameters();
			$omitYear = (isset($parameters['X-APPLE-OMIT-YEAR'])
					&& $parameters['X-APPLE-OMIT-YEAR'] === $dateParts['year']);
			// 'X-APPLE-OMIT-YEAR' is not always present, at least iOS 12.4 uses the hard coded date of 1604 (the start of the gregorian calendar) when the year is unknown
			if ($omitYear || (int)$dateParts['year'] === 1604) {
				$dateParts['year'] = null;
			}
		}

		$originalYear = null;
		if ($dateParts['year'] !== null) {
			$originalYear = (int)$dateParts['year'];
		}

		$leapDay = ((int)$dateParts['month'] === 2
				&& (int)$dateParts['date'] === 29);
		if ($dateParts['year'] === null || $originalYear < 1970) {
			$birthday = ($leapDay ? '1972-' : '1970-')
				. $dateParts['month'] . '-' . $dateParts['date'];
		}

		try {
			if ($birthday instanceof DateAndOrTime) {
				$date = $birthday->getDateTime();
			} else {
				$date = new \DateTimeImmutable($birthday);
			}
		} catch (Exception $e) {
			return null;
		}

		$summary = $this->formatTitle($dateField, $doc->FN->getValue(), $originalYear, $this->dbConnection->supports4ByteText());

		$vCal = new VCalendar();
		$vCal->VERSION = '2.0';
		$vCal->PRODID = '-//IDN nextcloud.com//Birthday calendar//EN';
		$vEvent = $vCal->createComponent('VEVENT');
		$vEvent->add('DTSTART');
		$vEvent->DTSTART->setDateTime(
			$date
		);
		$vEvent->DTSTART['VALUE'] = 'DATE';
		$vEvent->add('DTEND');

		$dtEndDate = (new \DateTime())->setTimestamp($date->getTimeStamp());
		$dtEndDate->add(new \DateInterval('P1D'));
		$vEvent->DTEND->setDateTime(
			$dtEndDate
		);

		$vEvent->DTEND['VALUE'] = 'DATE';
		$vEvent->{'UID'} = $doc->UID . $postfix;
		$vEvent->{'RRULE'} = 'FREQ=YEARLY';
		if ($leapDay) {
			/* Sabre\VObject supports BYMONTHDAY only if BYMONTH
			 * is also set */
			$vEvent->{'RRULE'} = 'FREQ=YEARLY;BYMONTH=2;BYMONTHDAY=-1';
		}
		$vEvent->{'SUMMARY'} = $summary;
		$vEvent->{'TRANSP'} = 'TRANSPARENT';
		$vEvent->{'X-NEXTCLOUD-BC-FIELD-TYPE'} = $dateField;
		$vEvent->{'X-NEXTCLOUD-BC-UNKNOWN-YEAR'} = $dateParts['year'] === null ? '1' : '0';
		if ($originalYear !== null) {
			$vEvent->{'X-NEXTCLOUD-BC-YEAR'} = (string) $originalYear;
		}
		if ($reminderOffset) {
			$alarm = $vCal->createComponent('VALARM');
			$alarm->add($vCal->createProperty('TRIGGER', $reminderOffset, ['VALUE' => 'DURATION']));
			$alarm->add($vCal->createProperty('ACTION', 'DISPLAY'));
			$alarm->add($vCal->createProperty('DESCRIPTION', $vEvent->{'SUMMARY'}));
			$vEvent->add($alarm);
		}
		$vCal->add($vEvent);
		return $vCal;
	}

	/**
	 * @param string $user
	 */
	public function resetForUser(string $user):void {
		$principal = 'principals/users/'.$user;
		$calendar = $this->calDavBackEnd->getCalendarByUri($principal, self::BIRTHDAY_CALENDAR_URI);
		if (!$calendar) {
			return; // The user's birthday calendar doesn't exist, no need to purge it
		}
		$calendarObjects = $this->calDavBackEnd->getCalendarObjects($calendar['id'], CalDavBackend::CALENDAR_TYPE_CALENDAR);

		foreach ($calendarObjects as $calendarObject) {
			$this->calDavBackEnd->deleteCalendarObject($calendar['id'], $calendarObject['uri'], CalDavBackend::CALENDAR_TYPE_CALENDAR, true);
		}
	}

	/**
	 * @param string $user
	 * @throws \Sabre\DAV\Exception\BadRequest
	 */
	public function syncUser(string $user):void {
		$principal = 'principals/users/'.$user;
		$this->ensureCalendarExists($principal);
		$books = $this->cardDavBackEnd->getAddressBooksForUser($principal);
		foreach ($books as $book) {
			$cards = $this->cardDavBackEnd->getCards($book['id']);
			foreach ($cards as $card) {
				$this->onCardChanged((int) $book['id'], $card['uri'], $card['carddata']);
			}
		}
	}

	/**
	 * @param string $existingCalendarData
	 * @param VCalendar $newCalendarData
	 * @return bool
	 */
	public function birthdayEvenChanged(string $existingCalendarData,
										VCalendar $newCalendarData):bool {
		try {
			$existingBirthday = Reader::read($existingCalendarData);
		} catch (Exception $ex) {
			return true;
		}

		return (
			$newCalendarData->VEVENT->DTSTART->getValue() !== $existingBirthday->VEVENT->DTSTART->getValue() ||
			$newCalendarData->VEVENT->SUMMARY->getValue() !== $existingBirthday->VEVENT->SUMMARY->getValue()
		);
	}

	/**
	 * @param integer $addressBookId
	 * @return mixed
	 */
	protected function getAllAffectedPrincipals(int $addressBookId) {
		$targetPrincipals = [];
		$shares = $this->cardDavBackEnd->getShares($addressBookId);
		foreach ($shares as $share) {
			if ($share['{http://owncloud.org/ns}group-share']) {
				$users = $this->principalBackend->getGroupMemberSet($share['{http://owncloud.org/ns}principal']);
				foreach ($users as $user) {
					$targetPrincipals[] = $user['uri'];
				}
			} else {
				$targetPrincipals[] = $share['{http://owncloud.org/ns}principal'];
			}
		}
		return array_values(array_unique($targetPrincipals, SORT_STRING));
	}

	/**
	 * @param string $cardUri
	 * @param string $cardData
	 * @param array $book
	 * @param int $calendarId
	 * @param array $type
	 * @param string $reminderOffset
	 * @throws InvalidDataException
	 * @throws \Sabre\DAV\Exception\BadRequest
	 */
	private function updateCalendar(string $cardUri,
									string $cardData,
									array $book,
									int $calendarId,
									array $type,
									?string $reminderOffset):void {
		$objectUri = $book['uri'] . '-' . $cardUri . $type['postfix'] . '.ics';
		$calendarData = $this->buildDateFromContact($cardData, $type['field'], $type['postfix'], $reminderOffset);
		$existing = $this->calDavBackEnd->getCalendarObject($calendarId, $objectUri);
		if ($calendarData === null) {
			if ($existing !== null) {
				$this->calDavBackEnd->deleteCalendarObject($calendarId, $objectUri, CalDavBackend::CALENDAR_TYPE_CALENDAR, true);
			}
		} else {
			if ($existing === null) {
				// not found by URI, but maybe by UID
				// happens when a contact with birthday is moved to a different address book
				$calendarInfo = $this->calDavBackEnd->getCalendarById($calendarId);
				$extraData = $this->calDavBackEnd->getDenormalizedData($calendarData->serialize());

				if ($calendarInfo && array_key_exists('principaluri', $calendarInfo)) {
					$existing2path = $this->calDavBackEnd->getCalendarObjectByUID($calendarInfo['principaluri'], $extraData['uid']);
					if ($existing2path !== null && array_key_exists('uri', $calendarInfo)) {
						// delete the old birthday entry first so that we do not get duplicate UIDs
						$existing2objectUri = substr($existing2path, strlen($calendarInfo['uri']) + 1);
						$this->calDavBackEnd->deleteCalendarObject($calendarId, $existing2objectUri, CalDavBackend::CALENDAR_TYPE_CALENDAR, true);
					}
				}

				$this->calDavBackEnd->createCalendarObject($calendarId, $objectUri, $calendarData->serialize());
			} else {
				if ($this->birthdayEvenChanged($existing['calendardata'], $calendarData)) {
					$this->calDavBackEnd->updateCalendarObject($calendarId, $objectUri, $calendarData->serialize());
				}
			}
		}
	}

	/**
	 * checks if the admin opted-out of birthday calendars
	 *
	 * @return bool
	 */
	private function isGloballyEnabled():bool {
		return $this->config->getAppValue('dav', 'generateBirthdayCalendar', 'yes') === 'yes';
	}

	/**
	 * Extracts the userId part of a principal
	 *
	 * @param string $userPrincipal
	 * @return string|null
	 */
	private function principalToUserId(string $userPrincipal):?string {
		if (substr($userPrincipal, 0, 17) === 'principals/users/') {
			return substr($userPrincipal, 17);
		}
		return null;
	}

	/**
	 * Checks if the user opted-out of birthday calendars
	 *
	 * @param string $userPrincipal The user principal to check for
	 * @return bool
	 */
	private function isUserEnabled(string $userPrincipal):bool {
		$userId = $this->principalToUserId($userPrincipal);
		if ($userId !== null) {
			$isEnabled = $this->config->getUserValue($userId, 'dav', 'generateBirthdayCalendar', 'yes');
			return $isEnabled === 'yes';
		}

		// not sure how we got here, just be on the safe side and return true
		return true;
	}

	/**
	 * Get the reminder offset value for a user. This is a duration string (e.g.
	 * PT9H) or null if no reminder is wanted.
	 *
	 * @param string $userPrincipal
	 * @return string|null
	 */
	private function getReminderOffsetForUser(string $userPrincipal):?string {
		$userId = $this->principalToUserId($userPrincipal);
		if ($userId !== null) {
			return $this->config->getUserValue($userId, 'dav', 'birthdayCalendarReminderOffset', 'PT9H') ?: null;
		}

		// not sure how we got here, just be on the safe side and return the default value
		return 'PT9H';
	}

	/**
	 * Formats title of Birthday event
	 *
	 * @param string $field Field name like BDAY, ANNIVERSARY, ...
	 * @param string $name Name of contact
	 * @param int|null $year Year of birth, anniversary, ...
	 * @param bool $supports4Byte Whether or not the database supports 4 byte chars
	 * @return string The formatted title
	 */
	private function formatTitle(string $field,
								 string $name,
								 int $year = null,
								 bool $supports4Byte = true):string {
		if ($supports4Byte) {
			switch ($field) {
				case 'BDAY':
					return implode('', [
						'🎂 ',
						$name,
						$year ? (' (' . $year . ')') : '',
					]);

				case 'DEATHDATE':
					return implode('', [
						$this->l10n->t('Death of %s', [$name]),
						$year ? (' (' . $year . ')') : '',
					]);

				case 'ANNIVERSARY':
					return implode('', [
						'💍 ',
						$name,
						$year ? (' (' . $year . ')') : '',
					]);

				default:
					return '';
			}
		} else {
			switch ($field) {
				case 'BDAY':
					return implode('', [
						$name,
						' ',
						$year ? ('(*' . $year . ')') : '*',
					]);

				case 'DEATHDATE':
					return implode('', [
						$this->l10n->t('Death of %s', [$name]),
						$year ? (' (' . $year . ')') : '',
					]);

				case 'ANNIVERSARY':
					return implode('', [
						$name,
						' ',
						$year ? ('(⚭' . $year . ')') : '⚭',
					]);

				default:
					return '';
			}
		}
	}
}
