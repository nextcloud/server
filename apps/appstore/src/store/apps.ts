/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAppBundle, IAppstoreApp, IAppstoreCategory, IAppstoreExApp } from '../apps.d.ts'

import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { defineStore } from 'pinia'
import { computed, readonly, ref } from 'vue'
import * as api from '../service/api.ts'
import { rebuildNavigation } from '../service/rebuild-navigation.ts'
import { canDisable, canInstall, canLimitToGroups, canUninstall, needForceEnable } from '../utils/appStatus.ts'
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
	 * All app bundles available in the appstore
	 */
	const bundles = readonly(loadState<IAppBundle[]>('appstore', 'appstoreBundles'))

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
	 * Get all apps of a category
	 *
	 * @param categoryId - The id of the category
	 */
	function getAppsByCategory(categoryId: string): (IAppstoreApp | IAppstoreExApp)[] {
		return apps.value.filter((app) => [app.category].flat().includes(categoryId))
	}

	/**
	 * Enable an app by its id
	 *
	 * @param appId - The app to enable
	 * @param force - Whether to force enable the app
	 */
	async function enableApp(appId: string, force = false) {
		const app = getAppById(appId)
		if (!app) {
			throw new Error(`App with id ${appId} not found`)
		}

		if (app.active || (!app.installed && !canInstall(app))) {
			throw new Error(`App with id ${appId} cannot be enabled`)
		}

		if (!force && needForceEnable(app)) {
			throw new Error(`App with id ${appId} requires force enable`)
		}

		app.loading = true
		try {
			if (app.app_api) {
				await exApps.enableApp(appId)
			} else {
				await api.enableApp(appId, force)
			}
			if (force) {
				app.isCompatible = true
			}
			app.active = true
			app.installed = true
			app.removable = true
			await rebuildNavigation()
		} finally {
			app.loading = false
		}
	}

	/**
	 * Disable an app by its id
	 *
	 * @param appId - The app to disable
	 */
	async function disableApp(appId: string) {
		const app = getAppById(appId)
		if (!app) {
			throw new Error(`App with id ${appId} not found`)
		}

		if (!canDisable(app)) {
			throw new Error(`App with id ${appId} cannot be disabled`)
		}

		app.loading = true
		try {
			if (app.app_api) {
				await exApps.disableApp(appId)
			} else {
				await api.disableApp(appId)
			}
			app.active = false
			// revert "force enable"
			app.isCompatible = app.missingDependencies === undefined || app.missingDependencies.length === 0
			await rebuildNavigation()
		} finally {
			app.loading = false
		}
	}

	/**
	 * Uninstall an app by its id
	 *
	 * @param appId - The app to uninstall
	 */
	async function uninstallApp(appId: string) {
		const app = getAppById(appId)
		if (!app) {
			throw new Error(`App with id ${appId} not found`)
		}

		if (!canUninstall(app)) {
			throw new Error(`App with id ${appId} cannot be uninstalled`)
		}

		app.loading = true
		try {
			if (app.app_api) {
				await exApps.uninstallApp(appId)
			} else {
				await api.uninstallApp(appId)
			}
			app.active = false
			app.installed = false
			await rebuildNavigation()
		} finally {
			app.loading = false
		}
	}

	/**
	 * Limit access to an app to specific groups
	 *
	 * @param appId - The app to limit access to
	 * @param groups - The groups which should have access
	 */
	async function limitAppToGroups(appId: string, groups: string[]) {
		const app = getAppById(appId)
		if (!app) {
			throw new Error(`App with id ${appId} not found`)
		}

		if (!canLimitToGroups(app)) {
			throw new Error(`App with id ${appId} cannot be limited to groups`)
		}

		if (app.app_api) {
			return
		}

		try {
			app.loading = true
			await api.enableApp(appId, false, groups)
			app.groups = groups
		} finally {
			app.loading = false
		}
	}

	/**
	 * Enable a whole bundle of apps by its id
	 *
	 * @param bundleId - The id of the bundle to enable
	 */
	async function enableBundle(bundleId: string) {
		const bundle = bundles.find((b) => b.id === bundleId)
		if (!bundle) {
			throw new Error(`Bundle with id ${bundleId} not found`)
		}

		try {
			for (const appId of bundle.appIdentifiers) {
				const app = getAppById(appId)!
				app.loading = true
			}
			await api.enableBundle(bundle.id)
			for (const appId of bundle.appIdentifiers) {
				const app = getAppById(appId)!
				app.active = true
				app.installed = true
				app.removable = true
				await rebuildNavigation()
			}
		} finally {
			for (const appId of bundle.appIdentifiers) {
				const app = getAppById(appId)!
				app.loading = false
			}
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
			showError(t('appstore', 'Could not load app categories. Please try again later.'))
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
			showError(t('appstore', 'Could not load apps list. Please try again later.'))
		} finally {
			isLoadingApps.value = false
		}
	}

	// initialize store
	loadApps()
	loadCategories()

	return {
		apps,
		bundles,
		categories,
		isLoadingApps,
		isLoadingCategories,

		disableApp,
		enableApp,
		uninstallApp,
		enableBundle,

		getAppById,
		getAppsByCategory,
		getCategoryById,
		limitAppToGroups,
	}
})
