/*!
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IClearAt } from './statusState.ts'

import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { fetchAllPredefinedStatuses } from '../services/predefinedStatusService.js'

export interface IPredefinedStatus {
	id: string
	icon: string
	message: string
	clearAt: IClearAt | null
	visible?: boolean
}

export const usePredefinedStatusesStore = defineStore('predefinedStatuses', () => {
	const predefinedStatuses = ref<IPredefinedStatus[]>([])

	const statusesHaveLoaded = computed(() => predefinedStatuses.value.length > 0)

	/**
	 * Load all predefined statuses from the server, unless they were already loaded.
	 */
	async function loadAllPredefinedStatuses() {
		if (statusesHaveLoaded.value) {
			return
		}

		predefinedStatuses.value = await fetchAllPredefinedStatuses() as IPredefinedStatus[]
	}

	return {
		predefinedStatuses,
		statusesHaveLoaded,
		loadAllPredefinedStatuses,
	}
})
