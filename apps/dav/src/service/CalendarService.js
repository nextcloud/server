/**
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license AGPL-3.0-or-later
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
 */
import { getClient } from '../dav/client.js'
import logger from './logger.js'
import { parseXML } from 'webdav'

import {
	slotsToVavailability,
	vavailabilityToSlots,
} from '@nextcloud/calendar-availability-vue'

/**
 *
 */
export function getEmptySlots() {
	return {
		MO: [],
		TU: [],
		WE: [],
		TH: [],
		FR: [],
		SA: [],
		SU: [],
	}
}

/**
 *
 */
export async function findScheduleInboxAvailability() {
	const client = getClient('calendars')

	const response = await client.customRequest('inbox', {
		method: 'PROPFIND',
		data: `<?xml version="1.0"?>
			<x0:propfind xmlns:x0="DAV:">
			  <x0:prop>
				<x1:calendar-availability xmlns:x1="urn:ietf:params:xml:ns:caldav"/>
			  </x0:prop>
			</x0:propfind>`,
	})

	const xml = await parseXML(response.data)

	if (!xml) {
		return undefined
	}

	const availability = xml?.multistatus?.response[0]?.propstat?.prop['calendar-availability']
	if (!availability) {
		return undefined
	}

	return vavailabilityToSlots(availability)
}

/**
 * @param {any} slots -
 * @param {any} timezoneId -
 */
export async function saveScheduleInboxAvailability(slots, timezoneId) {
	const all = [...Object.keys(slots).flatMap(dayId => slots[dayId].map(slot => ({
		...slot,
		day: dayId,
	})))]

	const vavailability = slotsToVavailability(all, timezoneId)

	logger.debug('New availability ical created', {
		vavailability,
	})

	const client = getClient('calendars')
	await client.customRequest('inbox', {
		method: 'PROPPATCH',
		data: `<?xml version="1.0"?>
			<x0:propertyupdate xmlns:x0="DAV:">
			  <x0:set>
				<x0:prop>
				  <x1:calendar-availability xmlns:x1="urn:ietf:params:xml:ns:caldav">${vavailability}</x1:calendar-availability>
				</x0:prop>
			  </x0:set>
			</x0:propertyupdate>`,
	})
}
