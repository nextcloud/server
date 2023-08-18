/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

interface Reminder {
	dueDate: null | Date
}

export const getReminder = async (fileId: number): Promise<Reminder> => {
	const url = generateOcsUrl('/apps/files_reminders/api/v1/{fileId}', { fileId })
	const response = await axios.get(url)
	const dueDate = response.data.ocs.data.dueDate ? new Date(response.data.ocs.data.dueDate) : null

	return {
		dueDate,
	}
}

export const setReminder = async (fileId: number, dueDate: Date): Promise<[]> => {
	const url = generateOcsUrl('/apps/files_reminders/api/v1/{fileId}', { fileId })

	const response = await axios.put(url, {
		dueDate: dueDate.toISOString(), // timezone of string is always UTC
	})

	return response.data.ocs.data
}

export const clearReminder = async (fileId: number): Promise<[]> => {
	const url = generateOcsUrl('/apps/files_reminders/api/v1/{fileId}', { fileId })
	const response = await axios.delete(url)

	return response.data.ocs.data
}
