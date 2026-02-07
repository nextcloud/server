/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { RouteRecordRaw } from 'vue-router'

import { loadState } from '@nextcloud/initial-state'
import { defineAsyncComponent } from 'vue'

const appstoreEnabled = loadState<boolean>('appstore', 'appstoreEnabled', true)

// Dynamic loading
const AppstoreDiscover = defineAsyncComponent(() => import('../views/AppstoreDiscover.vue'))
const AppstoreManage = defineAsyncComponent(() => import('../views/AppstoreManage.vue'))

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
				path: ':category(installed|enabled|disabled|updates)/:id?',
				name: 'apps-manage',
				component: AppstoreManage,
			},
			{
				path: ':category',
				name: 'apps-category',
				children: [{
					path: ':id',
					name: 'apps-details',
					component: {},
				}],
			},
		],
	},
]

export default routes
