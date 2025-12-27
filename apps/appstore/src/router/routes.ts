/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { RouteRecordRaw } from 'vue-router'

import { loadState } from '@nextcloud/initial-state'
import { defineAsyncComponent } from 'vue'
const appstoreEnabled = loadState<boolean>('settings', 'appstoreEnabled', true)

// Dynamic loading
const AppstoreDiscover = defineAsyncComponent(() => import('../views/AppstoreDiscover.vue'))

const routes: RouteRecordRaw[] = [
	{
		path: '/:index(index.php/)?settings/apps',
		name: 'apps',
		redirect: appstoreEnabled
			? {
					name: 'apps-discover',
				}
			: {
					name: 'apps-category',
					params: { category: 'installed' },
				},
		children: [
			{
				path: 'discover/:id?',
				name: 'apps-discover',
				component: AppstoreDiscover,
			},
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
