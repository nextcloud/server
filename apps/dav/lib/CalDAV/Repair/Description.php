<?php
/**
 * @copyright 2023, Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
namespace OCA\DAV\CalDAV\Repair;

use Sabre\VObject\Component;
use Sabre\VObject\Component\VCalendar;

class Description implements IRepairStep {

	private const X_ALT_DESC_PROP_NAME = "X-ALT-DESC";

	private const SUPPORTED_COMPONENTS = ['VEVENT', 'VTODO'];

	public function runOnCreate(): bool {
		return false;
	}

	public function onCalendarObjectChange(?VCalendar $oldVCalendar, ?VCalendar $newVCalendar, bool &$modified): void {
		$keyedOldComponents = [];
		foreach ($oldVCalendar->children() as $child) {
			if (!($child instanceof Component)) {
				continue;
			}
			$keyedOldComponents[$child->UID] = $child;
		}

		foreach (self::SUPPORTED_COMPONENTS as $supportedComponent) {
			foreach ($newVCalendar->{$supportedComponent} as $newComponent) {
				$this->onCalendarComponentChange($keyedOldComponents[$newComponent->UID], $newComponent, $modified);
			}
		}
	}

	public function onCalendarComponentChange(?Component $oldObject, ?Component $newObject, bool &$modified): void {
		// Get presence of description fields
		$hasOldDescription = isset($oldObject->DESCRIPTION);
		$hasNewDescription = isset($newObject->DESCRIPTION);
		$hasOldXAltDesc = isset($oldObject->{self::X_ALT_DESC_PROP_NAME});
		$hasNewXAltDesc = isset($newObject->{self::X_ALT_DESC_PROP_NAME});
		$hasOldAltRep = isset($oldObject->DESCRIPTION['ALTREP']);
		$hasNewAltRep = isset($newObject->DESCRIPTION['ALTREP']);

		// If all description fields are present, then verify consistency
		if ($hasOldDescription && $hasNewDescription && (($hasOldXAltDesc && $hasNewXAltDesc) || ($hasOldAltRep && $hasNewAltRep))) {
			// Compare descriptions
			$isSameDescription = (string) $oldObject->DESCRIPTION === (string) $newObject->DESCRIPTION;
			$isSameXAltDesc = (string) $oldObject->{self::X_ALT_DESC_PROP_NAME} === (string) $newObject->{self::X_ALT_DESC_PROP_NAME};
			$isSameAltRep = (string) $oldObject->DESCRIPTION['ALTREP'] === (string) $newObject->DESCRIPTION['ALTREP'];

			// If the description changed, but not the alternate one, then delete the latest
			if (!$isSameDescription && $isSameXAltDesc) {
				unset($newObject->{self::X_ALT_DESC_PROP_NAME});
				$modified = true;
			}
			if (!$isSameDescription && $isSameAltRep) {
				unset($newObject->DESCRIPTION['ALTREP']);
				$modified = true;
			}
		}
	}
}
