/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { RouteRecordRaw } from 'vue-router'

import { loadState } from '@nextcloud/initial-state'

const appstoreEnabled = loadState<boolean>('appstore', 'appstoreEnabled', true)

// Dynamic loading
const AppstoreDiscover = () => import('../views/AppstoreDiscover.vue')
const AppstoreManage = () => import('../views/AppstoreManage.vue')
const AppstoreBundles = () => import('../views/AppstoreBundles.vue')
const AppstoreBrowse = () => import('../views/AppstoreBrowse.vue')
const AppstoreSearch = () => import('../views/AppstoreSearch.vue')

const routes: RouteRecordRaw[] = [
	{
		path: '/:index(index.php/)?settings/apps',
		name: 'apps',
		redirect: appstoreEnabled
			? {
					name: 'apps-discover',
				}
			: {
					name: 'apps-manage',
					params: { category: 'installed' },
				},
		children: [
			{
				path: 'discover/:id?',
				name: 'apps-discover',
				component: AppstoreDiscover,
			},
			{
				path: 'bundles/:id?',
				name: 'apps-bundles',
				component: AppstoreBundles,
			},
			{
				path: ':category(installed|enabled|disabled|updates)/:id?',
				name: 'apps-manage',
				component: AppstoreManage,
			},
			{
				path: ':category/:id?',
				name: 'apps-category',
				component: AppstoreBrowse,
			},
			{
				path: 'search/:id?',
				name: 'apps-search',
				component: AppstoreSearch,
			},
		],
	},
]

export default routes
