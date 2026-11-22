/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'

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
	const internalUpdateCount = ref(loadState<number>('appstore', 'appstoreUpdateCount', 0))

	/**
	 * Total number of apps with available updates
	 */
	const updateCount = computed(() => internalUpdateCount.value + exApps.updateCount)

	/**
	 * Update multiple apps by their app ids at the same time
	 *
	 * @param appIds - Ids of apps to update
	 */
	async function updateApps(appIds: string[]) {
		const store = useAppsStore()

		const apps = appIds.map((appId) => store.getAppById(appId))
		if (apps.some((app) => !app)) {
			throw new Error('One or more apps not found')
		}

		const exAppsToUpdate = (apps as (IAppstoreApp | IAppstoreExApp)[]).filter((app): app is IAppstoreExApp => 'app_api' in app && app.app_api)
		for (const app of exAppsToUpdate) {
			try {
				await exApps.updateApp(app.id)
				app.update = undefined
				rebuildNavigation()
			} catch (error) {
				logger.error('Failed to update ex app', { appId: app.id, error })
				showError(t('appstore', 'Could not update an exApp. Please try again later.'))
			}
		}

		const appsToUpdate = (apps as (IAppstoreApp | IAppstoreExApp)[]).filter((app): app is IAppstoreApp => !('app_api' in app && app.app_api))
		if (appsToUpdate.length > 0) {
			try {
				const results = await Promise.allSettled(await api.updateApps(appsToUpdate.map((app) => app.id)))
				for (const [index, app] of appsToUpdate.entries()) {
					const result = results[index]
					if (result?.status === 'rejected') {
						logger.error('Failed to update app', { appId: app.id, error: result.reason })
						showError(t('appstore', 'Could not update {app}. Please try again later.', { app: app.name }))
					} else {
						app.update = undefined
						internalUpdateCount.value = Math.max(internalUpdateCount.value - 1, 0)
					}
				}
				rebuildNavigation()
			} catch (error) {
				logger.error('Failed to update one or more apps', { appsToUpdate, error })
				showError(t('appstore', 'Could not update an app. Please try again later.'))
			}
		}
	}

	/**
	 * Update the given app
	 *
	 * @param appId - The app id to update
	 * @throws {Error} if the app is not found
	 */
	async function updateApp(appId: string) {
		updateApps([appId])
	}

	return {
		updateCount,
		updateApp,
		updateApps,
	}
})
