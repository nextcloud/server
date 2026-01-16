/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as api from '../service/api.ts'
import { rebuildNavigation } from '../service/rebuild-navigation.ts'
import logger from '../utils/logger.ts'
import { useAppsStore } from './apps.ts'
import { useExAppsStore } from './exApps.ts'

export const useUpdatesStore = defineStore('updates', () => {
	const exApps = useExAppsStore()

	/**
	 * Number of apps with available updates
	 */
	const internalUpdateCount = ref(loadState<number>('settings', 'appstoreUpdateCount', 0))

	/**
	 * Total number of apps with available updates
	 */
	const updateCount = computed(() => internalUpdateCount.value + exApps.updateCount)

	/**
	 * Update the given app
	 *
	 * @param appId - The app id to update
	 * @throws {Error} if the app is not found
	 */
	async function updateApp(appId: string) {
		const store = useAppsStore()

		const app = store.getAppById(appId)
		if (!app) {
			throw new Error(`App with id ${appId} not found`)
		}

		try {
			if ('app_api' in app && app.app_api) {
				await exApps.updateApp(appId)
			} else {
				await api.updateApp(appId)
				internalUpdateCount.value = Math.max(internalUpdateCount.value - 1, 0)
			}

			rebuildNavigation()
		} catch (error) {
			logger.error('Failed to update app', { appId, error })
			showError(t('settings', 'Could not update the app. Please try again later.'))
		}
	}

	return {
		updateCount,
		updateApp,
	}
})
