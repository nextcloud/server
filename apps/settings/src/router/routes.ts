/*
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { RouteConfig } from 'vue-router'

const routes: RouteConfig[] = [
	{
		name: 'users',
		path: '/:index(index.php/)?settings/users',
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
