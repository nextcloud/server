/**
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
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
import { getClient } from '../dav/client'
import ICAL from 'ical.js'
import logger from './logger'
import { parseXML } from 'webdav/dist/node/tools/dav'
import { getZoneString } from 'icalzone'
import { v4 as uuidv4 } from 'uuid'

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

	const parsedIcal = ICAL.parse(availability)

	const vcalendarComp = new ICAL.Component(parsedIcal)
	const vavailabilityComp = vcalendarComp.getFirstSubcomponent('vavailability')

	let timezoneId
	const timezoneComp = vcalendarComp.getFirstSubcomponent('vtimezone')
	if (timezoneComp) {
		timezoneId = timezoneComp.getFirstProperty('tzid').getFirstValue()
	}

	const availableComps = vavailabilityComp.getAllSubcomponents('available')
	// Combine all AVAILABLE blocks into a week of slots
	const slots = getEmptySlots()
	availableComps.forEach((availableComp) => {
		const start = availableComp.getFirstProperty('dtstart').getFirstValue().toJSDate()
		const end = availableComp.getFirstProperty('dtend').getFirstValue().toJSDate()
		const rrule = availableComp.getFirstProperty('rrule')

		if (rrule.getFirstValue().freq !== 'WEEKLY') {
			logger.warn('rrule not supported', {
				rrule: rrule.toICALString(),
			})
			return
		}

		rrule.getFirstValue().getComponent('BYDAY').forEach(day => {
			slots[day].push({
				start,
				end,
			})
		})
	})

	return {
		slots,
		timezoneId,
	}
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

	const vcalendarComp = new ICAL.Component('vcalendar')
	vcalendarComp.addPropertyWithValue('prodid', 'Nextcloud DAV app')

	// Store time zone info
	// If possible we use the info from a time zone database
	const predefinedTimezoneIcal = getZoneString(timezoneId)
	if (predefinedTimezoneIcal) {
		const timezoneComp = new ICAL.Component(ICAL.parse(predefinedTimezoneIcal))
		vcalendarComp.addSubcomponent(timezoneComp)
	} else {
		// Fall back to a simple markup
		const timezoneComp = new ICAL.Component('vtimezone')
		timezoneComp.addPropertyWithValue('tzid', timezoneId)
		vcalendarComp.addSubcomponent(timezoneComp)
	}

	// Store availability info
	const vavailabilityComp = new ICAL.Component('vavailability')

	// Deduplicate by start and end time
	const deduplicated = all.reduce((acc, slot) => {
		const key = [
			slot.start.getHours(),
			slot.start.getMinutes(),
			slot.end.getHours(),
			slot.end.getMinutes(),
		].join('-')

		return {
			...acc,
			[key]: [...(acc[key] ?? []), slot],
		}
	}, {})

	// Create an AVAILABILITY component for every recurring slot
	Object.keys(deduplicated).map(key => {
		const slots = deduplicated[key]
		const start = slots[0].start
		const end = slots[0].end
		// Combine days but make them also unique
		const days = slots.map(slot => slot.day).filter((day, index, self) => self.indexOf(day) === index)

		const availableComp = new ICAL.Component('available')

		// Define DTSTART and DTEND
		const startTimeProp = availableComp.addPropertyWithValue('dtstart', ICAL.Time.fromJSDate(start, false))
		startTimeProp.setParameter('tzid', timezoneId)
		const endTimeProp = availableComp.addPropertyWithValue('dtend', ICAL.Time.fromJSDate(end, false))
		endTimeProp.setParameter('tzid', timezoneId)

		// Add mandatory UID
		availableComp.addPropertyWithValue('uid', uuidv4())

		// TODO: add optional summary

		// Define RRULE
		availableComp.addPropertyWithValue('rrule', {
			freq: 'WEEKLY',
			byday: days,
		})

		return availableComp
	}).map(vavailabilityComp.addSubcomponent.bind(vavailabilityComp))

	vcalendarComp.addSubcomponent(vavailabilityComp)
	logger.debug('New availability ical created', {
		asObject: vcalendarComp,
		asString: vcalendarComp.toString(),
	})

	const client = getClient('calendars')
	await client.customRequest('inbox', {
		method: 'PROPPATCH',
		data: `<?xml version="1.0"?>
			<x0:propertyupdate xmlns:x0="DAV:">
			  <x0:set>
				<x0:prop>
				  <x1:calendar-availability xmlns:x1="urn:ietf:params:xml:ns:caldav">${vcalendarComp.toString()}</x1:calendar-availability>
				</x0:prop>
			  </x0:set>
			</x0:propertyupdate>`,
	})
}
