/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { defineStore } from 'pinia'

export const useSearchStore = defineStore({
	id: 'search',

	state: () => ({
		externalFilters: [],
	}),

	actions: {
		registerExternalFilter({ id, appId, label, callback, icon }) {
			this.externalFilters.push({ id, appId, name: label, callback, icon, isPluginFilter: true })
		},
	},
})
