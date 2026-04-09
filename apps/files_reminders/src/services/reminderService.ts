/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

interface Reminder {
	dueDate: null | Date
}

/**
 * Get the reminder for a specific file
 *
 * @param fileId - The file id to get the reminder for
 */
export async function getReminder(fileId: number): Promise<Reminder> {
	const url = generateOcsUrl('/apps/files_reminders/api/v1/{fileId}', { fileId })
	const response = await axios.get(url)
	const dueDate = response.data.ocs.data.dueDate ? new Date(response.data.ocs.data.dueDate) : null

	return {
		dueDate,
	}
}

/**
 * Set a reminder for a specific file
 *
 * @param fileId - The file id to set the reminder for
 * @param dueDate - The due date for the reminder
 */
export async function setReminder(fileId: number, dueDate: Date): Promise<[]> {
	const url = generateOcsUrl('/apps/files_reminders/api/v1/{fileId}', { fileId })

	const response = await axios.put(url, {
		dueDate: dueDate.toISOString(), // timezone of string is always UTC
	})

	return response.data.ocs.data
}

/**
 * Clear the reminder for a specific file
 *
 * @param fileId - The file id to clear the reminder for
 */
export async function clearReminder(fileId: number): Promise<[]> {
	const url = generateOcsUrl('/apps/files_reminders/api/v1/{fileId}', { fileId })
	const response = await axios.delete(url)

	return response.data.ocs.data
}
