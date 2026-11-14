/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { flushPromises, mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { reactive } from 'vue'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import UserFormGroups from './UserFormGroups.vue'
import { formDataKey } from './injectionKeys.ts'

const searchGroups = vi.hoisted(() => vi.fn())

vi.mock('../../../../service/groups.ts', () => ({ searchGroups }))

describe('UserFormGroups', () => {
	it('lets the groups and the admin groups search at the same time', async () => {
		const pending: ((groups: unknown[]) => void)[] = []
		searchGroups.mockImplementation(() => new Promise((resolve) => pending.push(resolve)))
		const wrapper = mount(UserFormGroups, {
			global: {
				plugins: [createTestingPinia({
					createSpy: vi.fn,
					initialState: { 'settings-users': { serverData: { isAdmin: true } } },
				})],
				provide: { [formDataKey as symbol]: reactive({ groups: [], subadminGroups: [] }) },
			},
		})
		const [groupsSelect, subAdminSelect] = wrapper.findAllComponents(NcSelect)
		const groupsLoading = vi.fn()
		const subAdminLoading = vi.fn()

		groupsSelect!.vm.$emit('search', 'team', groupsLoading)
		subAdminSelect!.vm.$emit('search', 'sales', subAdminLoading)
		pending.forEach((resolve) => resolve([]))
		await flushPromises()

		expect(groupsLoading).toHaveBeenLastCalledWith(false)
		expect(subAdminLoading).toHaveBeenLastCalledWith(false)
	})
})
