<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\CalDAV;

use OCP\IL10N;
use Sabre\VObject\Component;
use Sabre\VObject\Property;
use Sabre\VObject\Reader;

class CalendarObject extends \Sabre\CalDAV\CalendarObject {

	/**
	 * CalendarObject constructor.
	 *
	 * @param CalDavBackend $caldavBackend
	 * @param IL10N $l10n
	 * @param array $calendarInfo
	 * @param array $objectData
	 */
	public function __construct(
		CalDavBackend $caldavBackend,
		protected IL10N $l10n,
		array $calendarInfo,
		array $objectData,
	) {
		parent::__construct($caldavBackend, $calendarInfo, $objectData);

		if ($this->isShared()) {
			unset($this->objectData['size']);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function get() {
		$data = parent::get();

		if (!$this->isShared()) {
			return $data;
		}

		$vObject = Reader::read($data);

		// remove VAlarms if calendar is shared read-only
		if (!$this->canWrite()) {
			$this->removeVAlarms($vObject);
		}

		// shows as busy if event is declared confidential
		if ($this->objectData['classification'] === CalDavBackend::CLASSIFICATION_CONFIDENTIAL) {
			$this->createConfidentialObject($vObject);
		}

		return $vObject->serialize();
	}

	public function getId(): int {
		return (int)$this->objectData['id'];
	}

	protected function isShared() {
		if (!isset($this->calendarInfo['{http://owncloud.org/ns}owner-principal'])) {
			return false;
		}

		return $this->calendarInfo['{http://owncloud.org/ns}owner-principal'] !== $this->calendarInfo['principaluri'];
	}

	/**
	 * @param Component\VCalendar $vObject
	 * @return void
	 */
	private function createConfidentialObject(Component\VCalendar $vObject): void {
		/** @var Component $vElement */
		$vElements = array_filter($vObject->getComponents(), static function ($vElement) {
			return $vElement instanceof Component\VEvent || $vElement instanceof Component\VJournal || $vElement instanceof Component\VTodo;
		});

		foreach ($vElements as $vElement) {
			if (empty($vElement->select('SUMMARY'))) {
				$vElement->add('SUMMARY', $this->l10n->t('Busy')); // This is needed to mask "Untitled Event" events
			}
			foreach ($vElement->children() as &$property) {
				/** @var Property $property */
				switch ($property->name) {
					case 'CREATED':
					case 'DTSTART':
					case 'RRULE':
					case 'RECURRENCE-ID':
					case 'RDATE':
					case 'DURATION':
					case 'DTEND':
					case 'CLASS':
					case 'EXRULE':
					case 'EXDATE':
					case 'UID':
						break;
					case 'SUMMARY':
						$property->setValue($this->l10n->t('Busy'));
						break;
					default:
						$vElement->__unset($property->name);
						unset($property);
						break;
				}
			}
		}
	}

	/**
	 * @param Component\VCalendar $vObject
	 * @return void
	 */
	private function removeVAlarms(Component\VCalendar $vObject) {
		$subcomponents = $vObject->getComponents();

		foreach ($subcomponents as $subcomponent) {
			unset($subcomponent->VALARM);
		}
	}

	/**
	 * @return bool
	 */
	private function canWrite() {
		if (isset($this->calendarInfo['{http://owncloud.org/ns}read-only'])) {
			return !$this->calendarInfo['{http://owncloud.org/ns}read-only'];
		}
		return true;
	}

	public function getCalendarId(): int {
		return (int)$this->objectData['calendarid'];
	}

	public function getPrincipalUri(): string {
		return $this->calendarInfo['principaluri'];
	}

	public function getOwner(): ?string {
		if (isset($this->calendarInfo['{http://owncloud.org/ns}owner-principal'])) {
			return $this->calendarInfo['{http://owncloud.org/ns}owner-principal'];
		}
		return parent::getOwner();
	}
}
