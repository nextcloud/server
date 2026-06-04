<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Service;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\Exception\ExampleEventException;
use OCA\DAV\Model\ExampleEvent;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\Security\ISecureRandom;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;

class ExampleEventService {
	private const FOLDER_NAME = 'example_event';
	private const FILE_NAME = 'example_event.ics';
	private const ENABLE_CONFIG_KEY = 'create_example_event';

	public function __construct(
		private readonly CalDavBackend $calDavBackend,
		private readonly ISecureRandom $random,
		private readonly ITimeFactory $time,
		private readonly IAppData $appData,
		private readonly IAppConfig $appConfig,
		private readonly IL10N $l10n,
	) {
	}

	public function createExampleEvent(int $calendarId): void {
		if (!$this->shouldCreateExampleEvent()) {
			return;
		}

		$exampleEvent = $this->getExampleEvent();
		$uid = $exampleEvent->getUid();
		$this->calDavBackend->createCalendarObject(
			$calendarId,
			"$uid.ics",
			$exampleEvent->getIcs(),
		);
	}

	private function getStartDate(): \DateTimeInterface {
		return $this->time->now()
			->add(new \DateInterval('P7D'))
			->setTime(10, 00);
	}

	private function getEndDate(): \DateTimeInterface {
		return $this->time->now()
			->add(new \DateInterval('P7D'))
			->setTime(11, 00);
	}

	private function getDefaultEvent(string $uid): VCalendar {
		$defaultDescription = $this->l10n->t(<<<EOF
Welcome to Nextcloud Calendar!

This is a sample event - explore the flexibility of planning with Nextcloud Calendar by making any edits you want!

With Nextcloud Calendar, you can:
- Create, edit, and manage events effortlessly.
- Create multiple calendars and share them with teammates, friends, or family.
- Check availability and display your busy times to others.
- Seamlessly integrate with apps and devices via CalDAV.
- Customize your experience: schedule recurring events, adjust notifications and other settings.
EOF);

		$vCalendar = new VCalendar();
		$props = [
			'UID' => $uid,
			'DTSTAMP' => $this->time->now(),
			'SUMMARY' => $this->l10n->t('Example event - open me!'),
			'DTSTART' => $this->getStartDate(),
			'DTEND' => $this->getEndDate(),
			'DESCRIPTION' => $defaultDescription,
		];
		$vCalendar->add('VEVENT', $props);
		return $vCalendar;
	}

	/**
	 * @return string|null The ics of the custom example event or null if no custom event was uploaded.
	 * @throws ExampleEventException If reading the custom ics file fails.
	 */
	private function getCustomExampleEvent(): ?string {
		try {
			$folder = $this->appData->getFolder(self::FOLDER_NAME);
			$icsFile = $folder->getFile(self::FILE_NAME);
		} catch (NotFoundException $e) {
			return null;
		}

		try {
			return $icsFile->getContent();
		} catch (NotFoundException|NotPermittedException $e) {
			throw new ExampleEventException(
				'Failed to read custom example event',
				0,
				$e,
			);
		}
	}

	/**
	 * Get the configured example event or the default one.
	 *
	 * @throws ExampleEventException If loading the custom example event fails.
	 */
	public function getExampleEvent(): ExampleEvent {
		$uid = $this->random->generate(32, ISecureRandom::CHAR_ALPHANUMERIC);
		$customIcs = $this->getCustomExampleEvent();
		if ($customIcs === null) {
			return new ExampleEvent($this->getDefaultEvent($uid), $uid);
		}

		[$vCalendar, $vEvent] = $this->parseEvent($customIcs);
		$vEvent->UID = $uid;
		$vEvent->DTSTART = $this->getStartDate();
		$vEvent->DTEND = $this->getEndDate();
		$vEvent->remove('ORGANIZER');
		$vEvent->remove('ATTENDEE');
		return new ExampleEvent($vCalendar, $uid);
	}

	/**
	 * @psalm-return list{VCalendar, VEvent} The VCALENDAR document and its VEVENT child component
	 * @throws ExampleEventException If parsing the event fails or if it is invalid.
	 */
	private function parseEvent(string $ics): array {
		try {
			$vCalendar = \Sabre\VObject\Reader::read($ics);
			if (!($vCalendar instanceof VCalendar)) {
				throw new ExampleEventException('Custom event does not contain a VCALENDAR component');
			}

			/** @var VEvent|null $vEvent */
			$vEvent = $vCalendar->getBaseComponent('VEVENT');
			if ($vEvent === null) {
				throw new ExampleEventException('Custom event does not contain a VEVENT component');
			}
		} catch (\Exception $e) {
			throw new ExampleEventException('Failed to parse custom event: ' . $e->getMessage(), 0, $e);
		}

		return [$vCalendar, $vEvent];
	}

	public function saveCustomExampleEvent(string $ics): void {
		// Parse and validate the event before attempting to save it to prevent run time errors
		$this->parseEvent($ics);

		try {
			$folder = $this->appData->getFolder(self::FOLDER_NAME);
		} catch (NotFoundException $e) {
			$folder = $this->appData->newFolder(self::FOLDER_NAME);
		}

		try {
			$existingFile = $folder->getFile(self::FILE_NAME);
			$existingFile->putContent($ics);
		} catch (NotFoundException $e) {
			$folder->newFile(self::FILE_NAME, $ics);
		}
	}

	public function deleteCustomExampleEvent(): void {
		try {
			$folder = $this->appData->getFolder(self::FOLDER_NAME);
			$file = $folder->getFile(self::FILE_NAME);
		} catch (NotFoundException $e) {
			return;
		}

		$file->delete();
	}

	public function hasCustomExampleEvent(): bool {
		try {
			return $this->getCustomExampleEvent() !== null;
		} catch (ExampleEventException $e) {
			return false;
		}
	}

	public function setCreateExampleEvent(bool $enable): void {
		$this->appConfig->setValueBool(Application::APP_ID, self::ENABLE_CONFIG_KEY, $enable);
	}

	public function shouldCreateExampleEvent(): bool {
		return $this->appConfig->getValueBool(Application::APP_ID, self::ENABLE_CONFIG_KEY, true);
	}
}
