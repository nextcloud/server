/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import { groupRouteParam } from '../composables/useGroupsNavigation.ts'
import routes from './routes.ts'

const router = createRouter({ history: createMemoryHistory(), routes })

describe('account management routes', () => {
	it.each([
		'/settings/users',
		'/index.php/settings/users',
	])('shows all accounts on %s', (path) => {
		const route = router.resolve(path)

		expect(route.name).toBe('users')
		expect(route.params.selectedGroup).toBeUndefined()
	})

	it.each([
		['/settings/users/admin', 'admin'],
		['/index.php/settings/users/disabled', 'disabled'],
		['/settings/users/Team%20%26%20Co%2050%25', 'Team & Co 50%'],
	])('opens the group of %s', (path, group) => {
		const route = router.resolve(path)

		expect(route.name).toBe('group')
		expect(route.params.selectedGroup).toBe(group)
	})

	it('builds a URL for a group id that needs encoding', () => {
		const route = router.resolve({ name: 'group', params: { selectedGroup: 'Team & Co 50%' } })

		expect(router.resolve(route.fullPath).params.selectedGroup).toBe('Team & Co 50%')
	})

	it('keeps the group URLs of the Vue 2 version', () => {
		const route = router.resolve({ name: 'group', params: { selectedGroup: groupRouteParam('dept/sales & Co') } })

		expect(route.fullPath).toBe('/settings/users/dept%252Fsales%2520%2526%2520Co')
	})

	it('navigates to a group', async () => {
		await router.push({ name: 'group', params: { selectedGroup: 'admin' } })

		expect(router.currentRoute.value.params.selectedGroup).toBe('admin')
	})
})
