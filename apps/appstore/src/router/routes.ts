/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { RouteConfig } from 'vue-router'

import { loadState } from '@nextcloud/initial-state'

const appstoreEnabled = loadState<boolean>('settings', 'appstoreEnabled', true)

// Dynamic loading
const AppStore = () => import(/* webpackChunkName: 'settings-apps-view' */'../views/AppStore.vue')
const AppStoreNavigation = () => import(/* webpackChunkName: 'settings-apps-view' */'../views/AppStoreNavigation.vue')
const AppStoreSidebar = () => import(/* webpackChunkName: 'settings-apps-view' */'../views/AppStoreSidebar.vue')

const routes: RouteConfig[] = [
	{
		path: '/:index(index.php/)?settings/apps',
		name: 'apps',
		redirect: {
			name: 'apps-category',
			params: {
				category: appstoreEnabled ? 'discover' : 'installed',
			},
		},
		components: {
			default: AppStore,
			navigation: AppStoreNavigation,
			sidebar: AppStoreSidebar,
		},
		children: [
			{
				path: ':category',
				name: 'apps-category',
				children: [
					{
						path: ':id',
						name: 'apps-details',
					},
				],
			},
		],
	},
]

export default routes
