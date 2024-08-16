/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { defineStore } from 'pinia'
import type { ActionsMenuStore } from '../types'

export const useActionsMenuStore = defineStore('actionsmenu', {
	state: () => ({
		opened: null,
	} as ActionsMenuStore),
})
