/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
