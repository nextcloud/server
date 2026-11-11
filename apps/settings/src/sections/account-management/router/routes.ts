/*
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { RouteRecordRaw } from 'vue-router'

// The page reads the route params itself, so the routes render nothing.
// An object, a plain function would be taken for a lazy loaded component.
const component = { render: () => null }

// Siblings instead of nested routes, so the "All accounts" link is not active on group pages too
const routes: RouteRecordRaw[] = [
	{
		name: 'users',
		path: '/:index(index.php/)?settings/users',
		component,
	},
	{
		name: 'group',
		path: '/:index(index.php/)?settings/users/:selectedGroup',
		component,
	},
]

export default routes
