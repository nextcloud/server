/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { ref } from 'vue'

export interface IUserStatus {
	/** Status (online / away / dnd / invisible / offline) */
	status: string | null
	/** Whether the status is user-defined */
	statusIsUserDefined: boolean | null
	/** A custom message set by the user */
	message: string | null
	/** The icon selected by the user */
	icon: string | null
	/** When to automatically clear the status (UNIX timestamp in seconds) */
	clearAt: number | null
	/** Whether the message is predefined (and can automatically be translated by Nextcloud) */
	messageIsPredefined: boolean | null
	/** The id of the message in case it's predefined */
	messageId: string | null
}

export interface IClearAt {
	type: 'period' | 'end-of' | '_time'
	time: number | string
}

/**
 * Reactive status state shared by the user status and the backup status stores.
 */
export function useStatusState() {
	const status = ref<string | null>(null)
	const statusIsUserDefined = ref<boolean | null>(null)
	const message = ref<string | null>(null)
	const icon = ref<string | null>(null)
	const clearAt = ref<number | null>(null)
	const messageIsPredefined = ref<boolean | null>(null)
	const messageId = ref<string | null>(null)

	/**
	 * Load a status as returned by the server.
	 * Only status, message and icon are always replaced, because partial updates
	 * (e.g. from the Talk participant list) only carry those fields.
	 *
	 * @param data - The status to load
	 */
	function loadStatus(data: Partial<IUserStatus>) {
		status.value = data.status ?? null
		message.value = data.message ?? null
		icon.value = data.icon ?? null

		if (data.statusIsUserDefined !== undefined) {
			statusIsUserDefined.value = data.statusIsUserDefined
		}
		if (data.clearAt !== undefined) {
			clearAt.value = data.clearAt
		}
		if (data.messageIsPredefined !== undefined) {
			messageIsPredefined.value = data.messageIsPredefined
		}
		if (data.messageId !== undefined) {
			messageId.value = data.messageId
		}
	}

	return {
		status,
		statusIsUserDefined,
		message,
		icon,
		clearAt,
		messageIsPredefined,
		messageId,
		loadStatus,
	}
}
