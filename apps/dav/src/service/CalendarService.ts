/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import {
	slotsToVavailability,
	vavailabilityToSlots,
} from '@nextcloud/calendar-availability-vue'
import { parseXML } from 'webdav'
import { getClient } from '../dav/client.ts'
import { logger } from './logger.ts'

/**
 * Get an object representing empty time slots for each day of the week.
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
 * Find the availability of the schedule inbox.
 */
export async function findScheduleInboxAvailability() {
	const response = await getClient().customRequest('inbox', {
		method: 'PROPFIND',
		data: `<?xml version="1.0"?>
			<x0:propfind xmlns:x0="DAV:">
			  <x0:prop>
				<x1:calendar-availability xmlns:x1="urn:ietf:params:xml:ns:caldav"/>
			  </x0:prop>
			</x0:propfind>`,
	})

	const xml = await parseXML(await response.text())

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
 * Save the availability of the schedule inbox.
 *
 * @param slots - The availability slots to save.
 * @param timezoneId - The timezone identifier.
 */
export async function saveScheduleInboxAvailability(slots, timezoneId) {
	const all = [...Object.keys(slots).flatMap((dayId) => slots[dayId].map((slot) => ({
		...slot,
		day: dayId,
	})))]

	const vavailability = slotsToVavailability(all, timezoneId)

	logger.debug('New availability ical created', {
		vavailability,
	})

	await getClient().customRequest('inbox', {
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
