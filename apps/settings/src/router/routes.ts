/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { RouteConfig } from 'vue-router'

const UserManagement = () => import(/* webpackChunkName: 'settings-users' */'../views/UserManagement.vue')
const UserManagementNavigation = () => import(/* webpackChunkName: 'settings-users' */'../views/UserManagementNavigation.vue')

const routes: RouteConfig[] = [
	{
		name: 'users',
		path: '/:index(index.php/)?settings/users',
		components: {
			default: UserManagement,
			navigation: UserManagementNavigation,
		},
		props: true,
		children: [
			{
				path: ':selectedGroup',
				name: 'group',
			},
		],
	},
]

export default routes
