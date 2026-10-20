/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import { ref } from 'vue'

/** A filter action other apps add through `OCA.UnifiedSearch.registerFilterAction` */
export interface UnifiedSearchAction {
	id: string
	appId: string
	searchFrom: string
	label: string
	icon: string
	callback: (isFilterApplied: boolean) => void
}

/** A registered filter action as offered next to the search providers */
export interface ExternalFilter extends Omit<UnifiedSearchAction, 'label'> {
	name: string
	isPluginFilter: true
}

export const useSearchStore = defineStore('search', () => {
	const externalFilters = ref<ExternalFilter[]>([])

	/**
	 * Offer the filter action of an app next to the search providers.
	 *
	 * @param action - The filter action to register
	 */
	function registerExternalFilter(action: UnifiedSearchAction): void {
		const { id, appId, searchFrom, label, callback, icon } = action
		externalFilters.value.push({ id, appId, searchFrom, name: label, callback, icon, isPluginFilter: true })
	}

	return {
		externalFilters,
		registerExternalFilter,
	}
})
