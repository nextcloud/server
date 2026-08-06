/*!
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 */

import type { MaybeRefOrGetter } from 'vue'
import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'

import { computed, toValue } from 'vue'
import { useRoute } from 'vue-router'
import { useUserSettingsStore } from '../store/userSettings.ts'

/**
 * Get the filtered list of apps based on the user settings
 *
 * @param apps - The apps to filter
 */
export function useFilteredApps(apps: MaybeRefOrGetter<(IAppstoreApp | IAppstoreExApp)[]>) {
	const store = useUserSettingsStore()
	const route = useRoute()
	return computed(() => {
		const query = [route.query.q || ''].flat()[0]!
		return toValue(apps)
			.filter((app) => {
				if (!store.showIncompatible && app.isCompatible === false) {
					return false
				}
				if (query) {
					const needle = query.trim().toLocaleLowerCase()
					return app.name.toLocaleLowerCase().includes(needle)
						|| app.id.toLocaleLowerCase().includes(needle)
						|| app.summary.toLocaleLowerCase().includes(needle)
				}
				return true
			})
	})
}
