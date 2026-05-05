/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { OCSResponse } from '@nextcloud/typings/ocs'
import type { IAppstoreApp, IAppstoreCategory } from '../app-types.ts'

import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { defineStore } from 'pinia'
import APPSTORE_CATEGORY_ICONS from '../constants/AppstoreCategoryIcons.ts'
import logger from '../utils/logger.ts'

const showApiError = () => showError(t('appstore', 'An error occurred during the request. Unable to proceed.'))

export const useAppsStore = defineStore('appstore-apps', {
	state: () => ({
		apps: [] as IAppstoreApp[],
		categories: [] as IAppstoreCategory[],
		updateCount: loadState<number>('appstore', 'appstoreUpdateCount', 0),
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
				const url = generateOcsUrl('apps/appstore/api/v1/apps/categories')
				const { data } = await axios.get<OCSResponse<IAppstoreCategory[]>>(url)

				const categories = data.ocs.data
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
				const url = generateOcsUrl('apps/appstore/api/v1/apps')
				const { data } = await axios.get<OCSResponse<IAppstoreApp[]>>(url)

				this.$patch({
					apps: data.ocs.data,
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

		getAppById(appId: string): IAppstoreApp | null {
			return this.apps.find(({ id }) => id === appId) ?? null
		},

		updateAppGroups(appId: string, groups: string[]) {
			const app = this.apps.find(({ id }) => id === appId)
			if (app) {
				app.groups = [...groups]
			}
		},
	},
})
