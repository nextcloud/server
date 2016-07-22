<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */


namespace OCA\DAV\CalDAV;


use Sabre\VObject\Component;
use Sabre\VObject\Property;
use Sabre\VObject\Reader;

class CalendarObject extends \Sabre\CalDAV\CalendarObject {

	/**
	 * @inheritdoc
	 */
	function get() {
		$data = parent::get();
		if ($this->isShared() && $this->objectData['classification'] === CalDavBackend::CLASSIFICATION_CONFIDENTIAL) {
			return $this->createConfidentialObject($data);
		}
		return $data;
	}

	private function isShared() {
		return isset($this->calendarInfo['{http://owncloud.org/ns}owner-principal']);
	}

	/**
	 * @param string $calData
	 * @return string
	 */
	private static function createConfidentialObject($calData) {

		$vObject = Reader::read($calData);

		/** @var Component $vElement */
		$vElement = null;
		if(isset($vObject->VEVENT)) {
			$vElement = $vObject->VEVENT;
		}
		if(isset($vObject->VJOURNAL)) {
			$vElement = $vObject->VJOURNAL;
		}
		if(isset($vObject->VTODO)) {
			$vElement = $vObject->VTODO;
		}
		if(!is_null($vElement)) {
			foreach ($vElement->children as &$property) {
				/** @var Property $property */
				switch($property->name) {
					case 'CREATED':
					case 'DTSTART':
					case 'RRULE':
					case 'DURATION':
					case 'DTEND':
					case 'CLASS':
					case 'UID':
						break;
					case 'SUMMARY':
						$property->setValue('Busy');
						break;
					default:
						$vElement->__unset($property->name);
						unset($property);
						break;
				}
			}
		}
		
		return $vObject->serialize();
	}

}
