/*!
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IClearAt, IUserStatus } from './statusState.ts'

import { getCurrentUser } from '@nextcloud/auth'
import { emit } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { defineStore } from 'pinia'
import { getTimestampForClearAt } from '../services/clearAtService.js'
import * as api from '../services/statusService.js'
import { usePredefinedStatusesStore } from './predefinedStatuses.ts'
import { useStatusState } from './statusState.ts'

export const useUserStatusStore = defineStore('userStatus', () => {
	const state = useStatusState()
	const { status, statusIsUserDefined, message, icon, clearAt, messageIsPredefined, messageId } = state

	/**
	 * Notify other components and apps about the changed status.
	 */
	function emitStatusUpdated() {
		emit('user_status:status.updated', {
			status: status.value,
			message: message.value,
			icon: icon.value,
			clearAt: clearAt.value,
			userId: getCurrentUser()?.uid,
		})
	}

	/**
	 * Load a status as returned by the server, e.g. from the heartbeat or a `user_status:status.updated` event.
	 * This does not emit a `user_status:status.updated` event.
	 *
	 * @param data - The status to load
	 */
	function loadStatusFromServer(data: Partial<IUserStatus>) {
		state.loadStatus(data)
	}

	/**
	 * Load the status from the initial state.
	 */
	function loadStatusFromInitialState() {
		loadStatusFromServer(loadState<IUserStatus>('user_status', 'status'))
	}

	/**
	 * Re-fetch the status from the server.
	 */
	async function reFetchStatusFromServer() {
		loadStatusFromServer(await api.fetchCurrentStatus())
	}

	/**
	 * Set a new status type.
	 *
	 * @param statusType - The new status (online / away / dnd / invisible)
	 */
	async function setStatus(statusType: string) {
		await api.setStatus(statusType)
		status.value = statusType
		statusIsUserDefined.value = true
		emitStatusUpdated()
	}

	/**
	 * Set the status message from a predefined status.
	 *
	 * @param newMessageId - The id of the predefined status
	 * @param newClearAt - When to automatically clear the status
	 */
	async function setPredefinedMessage(newMessageId: string, newClearAt: IClearAt | null) {
		const resolvedClearAt = getTimestampForClearAt(newClearAt)
		await api.setPredefinedMessage(newMessageId, resolvedClearAt)

		const predefinedStatus = usePredefinedStatusesStore().predefinedStatuses
			.find(({ id }) => id === newMessageId)

		messageId.value = newMessageId
		messageIsPredefined.value = true
		message.value = predefinedStatus?.message ?? null
		icon.value = predefinedStatus?.icon ?? null
		clearAt.value = resolvedClearAt
		emitStatusUpdated()
	}

	/**
	 * Set a custom status message.
	 *
	 * @param newMessage - The message
	 * @param newIcon - The icon
	 * @param newClearAt - When to automatically clear the status
	 */
	async function setCustomMessage(newMessage: string, newIcon: string | null, newClearAt: IClearAt | null) {
		const resolvedClearAt = getTimestampForClearAt(newClearAt)
		await api.setCustomMessage(newMessage, newIcon, resolvedClearAt)

		messageId.value = null
		messageIsPredefined.value = false
		message.value = newMessage
		icon.value = newIcon
		clearAt.value = resolvedClearAt
		emitStatusUpdated()
	}

	/**
	 * Clear the status message.
	 */
	async function clearMessage() {
		await api.clearMessage()

		messageId.value = null
		messageIsPredefined.value = false
		message.value = null
		icon.value = null
		clearAt.value = null
		emitStatusUpdated()
	}

	return {
		status,
		statusIsUserDefined,
		message,
		icon,
		clearAt,
		messageIsPredefined,
		messageId,

		emitStatusUpdated,
		loadStatusFromServer,
		loadStatusFromInitialState,
		reFetchStatusFromServer,
		setStatus,
		setPredefinedMessage,
		setCustomMessage,
		clearMessage,
	}
})
