/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
