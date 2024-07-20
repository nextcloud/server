/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAppstoreApp, IAppstoreCategory } from '../app-types.ts'

import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { defineStore } from 'pinia'

import axios from '@nextcloud/axios'

import logger from '../logger'
import APPSTORE_CATEGORY_ICONS from '../constants/AppstoreCategoryIcons.ts'

const showApiError = () => showError(t('settings', 'An error occurred during the request. Unable to proceed.'))

export const useAppsStore = defineStore('settings-apps', {
	state: () => ({
		apps: [] as IAppstoreApp[],
		categories: [] as IAppstoreCategory[],
		updateCount: loadState<number>('settings', 'appstoreUpdateCount', 0),
		loading: {
			apps: false,
			categories: false,
		},
		loadingList: false,
		gettingCategoriesPromise: null,
	}),

	actions: {
		async loadCategories(force = false) {
			if (this.categories.length > 0 && !force) {
				return
			}

			try {
				this.loading.categories = true
				const { data: categories } = await axios.get<IAppstoreCategory[]>(generateUrl('settings/apps/categories'))

				for (const category of categories) {
					category.icon = APPSTORE_CATEGORY_ICONS[category.id] ?? ''
				}

				this.$patch({
					categories,
				})
			} catch (error) {
				logger.error(error as Error)
				showApiError()
			} finally {
				this.loading.categories = false
			}
		},

		async loadApps(force = false) {
			if (this.apps.length > 0 && !force) {
				return
			}

			try {
				this.loading.apps = true
				const { data } = await axios.get<{ apps: IAppstoreApp[] }>(generateUrl('settings/apps/list'))

				this.$patch({
					apps: data.apps,
				})
			} catch (error) {
				logger.error(error as Error)
				showApiError()
			} finally {
				this.loading.apps = false
			}
		},

		getCategoryById(categoryId: string) {
			return this.categories.find(({ id }) => id === categoryId) ?? null
		},

		getAppById(appId: string): IAppstoreApp|null {
			return this.apps.find(({ id }) => id === appId) ?? null
		},
	},
})
