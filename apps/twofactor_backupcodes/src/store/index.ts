/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ITwoFactorBackupCodesState } from '../service/BackupCodesService.ts'

import { loadState } from '@nextcloud/initial-state'
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { generateCodes } from '../service/BackupCodesService.ts'

const initialState = loadState<ITwoFactorBackupCodesState>('twofactor_backupcodes', 'state')

export const useStore = defineStore('twofactor_backupcodes', () => {
	const enabled = ref(initialState.enabled)
	const total = ref(initialState.total)
	const used = ref(initialState.used)
	const codes = ref<string[]>([])

	/**
	 * Generate new backup codes and update the store state
	 */
	async function generate(): Promise<void> {
		enabled.value = false

		const { codes: newCodes, state } = await generateCodes()
		enabled.value = state.enabled
		total.value = state.total
		used.value = state.used
		codes.value = newCodes
	}

	return {
		enabled,
		total,
		used,
		codes,

		generate,
	}
})
