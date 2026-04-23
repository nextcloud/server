/*!
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 */

import type { MaybeRefOrGetter } from 'vue'
import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'

import { computed, toValue } from 'vue'
import { useUserSettingsStore } from '../store/userSettings.ts'

/**
 * Get the filtered list of apps based on the user settings
 *
 * @param apps - The apps to filter
 */
export function useFilteredApps(apps: MaybeRefOrGetter<(IAppstoreApp | IAppstoreExApp)[]>) {
	const store = useUserSettingsStore()
	return computed(() => {
		if (!store.showIncompatible) {
			return toValue(apps)
				.filter((app) => app.isCompatible !== false)
		}
		return toValue(apps)
	})
}
