/**
 * @copyright 2022 Cédric Neukom <github@webguy.ch>
 *
 * @author 2022 Cédric Neukom <github@webguy.ch>
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
import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import { getClient } from '../dav/client'

const CALDAV_BIRTHDAY_CALENDAR = 'contact_birthdays'

/**
 * Disable birthday calendar
 *
 * @returns {Promise<void>}
 */
export async function disableBirthdayCalendar() {
	const client = getClient('calendars')
	await client.customRequest(CALDAV_BIRTHDAY_CALENDAR, {
		method: 'DELETE',
	})
}

/**
 * Enable birthday calendar
 *
 * @returns {Promise<void>}
 */
export async function enableBirthdayCalendar() {
	const client = getClient('calendars')
	await client.customRequest('', {
		method: 'POST',
		data: '<x3:enable-birthday-calendar xmlns:x3="http://nextcloud.com/ns"/>',
	})
}

/**
 * Save birthday reminder offset. Value must be a duration (e.g. -PT15H)
 *
 * @param reminderOffset
 * @returns {Promise<AxiosResponse<any>>}
 */
export async function saveBirthdayReminder(reminderOffset) {
	return await axios.post(
		generateOcsUrl('/apps/provisioning_api/api/v1/config/users/{appId}/{configKey}', {
			appId: 'dav',
			configKey: 'birthdayCalendarReminderOffset',
		}),
		{
			configValue: reminderOffset,
		}
	)
}
