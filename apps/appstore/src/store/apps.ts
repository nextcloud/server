/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAppstoreApp, IAppstoreCategory, IAppstoreExApp } from '../apps.d.ts'

import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as api from '../service/api.ts'
import logger from '../utils/logger.ts'
import { useExAppsStore } from './exApps.ts'

export const useAppsStore = defineStore('apps', () => {
	const exApps = useExAppsStore()

	/**
	 * All apps available in the appstore
	 */
	const appstoreApps = ref<IAppstoreApp[]>([])
	/**
	 * All app categories available in the appstore
	 */
	const categories = ref<IAppstoreCategory[]>([])
	/**
	 * Loading state of the store
	 */
	const isLoadingApps = ref(false)
	const isLoadingCategories = ref(false)

	/**
	 * All apps available
	 */
	const apps = computed(() => [...appstoreApps.value, ...(exApps.isEnabled ? exApps.apps : [])])

	/**
	 * Get a category by its id
	 *
	 * @param categoryId - The id of the category
	 */
	function getCategoryById(categoryId: string) {
		return categories.value.find(({ id }) => id === categoryId) ?? null
	}

	/**
	 * Get an app by its id
	 *
	 * @param appId - The id of the app
	 */
	function getAppById(appId: string): IAppstoreApp | IAppstoreExApp | null {
		return apps.value.find(({ id }) => id === appId) ?? null
	}

	/**
	 *
	 * @param appId - The app to update
	 * @param groups - The new groups
	 */
	function updateAppGroups(appId: string, groups: string[]) {
		const app = apps.value.find(({ id }) => id === appId)
		if (app) {
			app.groups = [...groups]
		}
	}

	/**
	 * Load the app categories from the backend
	 */
	async function loadCategories() {
		try {
			isLoadingCategories.value = true
			categories.value = await api.getCategories()
		} catch (error) {
			logger.error('Failed to load app categories', { error })
			showError(t('settings', 'Could not load app categories. Please try again later.'))
		} finally {
			isLoadingCategories.value = false
		}
	}

	/**
	 * Load the apps from the backend
	 */
	async function loadApps() {
		try {
			isLoadingApps.value = true
			appstoreApps.value = await api.getApps()
		} catch (error) {
			logger.error('Failed to load apps list', { error })
			showError(t('settings', 'Could not load apps list. Please try again later.'))
		} finally {
			isLoadingApps.value = false
		}
	}

	// initialize store
	loadApps()
	loadCategories()

	return {
		apps,
		categories,
		isLoadingApps,
		isLoadingCategories,

		getAppById,
		getCategoryById,
		updateAppGroups,
	}
})
