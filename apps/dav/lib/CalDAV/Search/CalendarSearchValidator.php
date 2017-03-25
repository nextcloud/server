<?php
/**
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @copyright Copyright (c) 2017 Georg Ehrke <oc.list@georgehrke.com>
 * @license GNU AGPL version 3 or any later version
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
namespace OCA\DAV\CalDAV\Search;

use Sabre\VObject;

class CalendarSearchValidator {

	/**
	 * Verify if a list of filters applies to the calendar data object
	 *
	 * The list of filters must be formatted as parsed by Xml\Request\CalendarSearchReport
	 *
	 * @param VObject\Component\VCalendar $vObject
	 * @param array $filters
	 * @return bool
	 */
	function validate(VObject\Component\VCalendar $vObject, array $filters) {
		$comps = $vObject->getComponents();
		$filters['comps'][] = 'VTIMEZONE';

		$matches = false;
		foreach($comps as $comp) {
			if ($comp->name === 'VTIMEZONE') {
				continue;
			}
			if ($matches) {
				break;
			}

			// check comps
			if (!in_array($comp->name, $filters['comps'])) {
				return false;
			}

			$children = $comp->children();
			foreach($children as $child) {
				if (!($child instanceof VObject\Property)) {
					continue;
				}
				if ($matches) {
					break;
				}

				foreach($filters['props'] as $prop) {
					if ($child->name !== $prop) {
						continue;
					}

					$value = $child->getValue();
					if (substr_count($value, $filters['search-term'])) {
						$matches = true;
						break;
					}
				}

				foreach($filters['params'] as $param) {
					$propName = $param['property'];
					$paramName = $param['parameter'];

					if ($child->name !== $propName) {
						continue;
					}
					if ($matches) {
						break;
					}

					$parameters = $child->parameters();
					foreach ($parameters as $key => $value) {
						if ($paramName !== $key) {
							continue;
						}
						if (substr_count($value, $filters['search-term'])) {
							$matches = true;
							break;
						}
					}
				}
			}
		}

		return $matches;
	}
}
