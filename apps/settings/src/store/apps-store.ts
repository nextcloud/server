/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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

		getAppById(appId: string) {
			return this.apps.find(({ id }) => id === appId) ?? null
		},
	},
})
