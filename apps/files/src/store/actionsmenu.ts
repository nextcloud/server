/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ActionsMenuStore } from '../types.ts'

import { defineStore } from 'pinia'

export const useActionsMenuStore = defineStore('actionsmenu', {
	state: () => ({
		opened: null,
	} as ActionsMenuStore),
})
