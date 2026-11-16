/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { flushPromises, mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import UserList from './UserList.vue'
import { useUsersStore } from '../store/users.ts'

const showError = vi.hoisted(() => vi.fn())

vi.mock('@nextcloud/dialogs', () => ({ showError, showWarning: vi.fn() }))
vi.mock('vue-router', () => ({ useRouter: () => ({ push: vi.fn() }) }))

describe('UserList', () => {
	it('tells the user when the accounts cannot be loaded', async () => {
		const wrapper = mount(UserList, {
			global: {
				plugins: [createTestingPinia({
					createSpy: vi.fn,
					initialState: {
						'settings-users': {
							serverData: { quotaPreset: [], languages: { commonLanguages: [], otherLanguages: [] } },
							showConfig: {},
						},
					},
				})],
			},
		})
		const store = useUsersStore()
		vi.mocked(store.getUsers).mockRejectedValue(new Error('500'))

		store.searchQuery = 'bob'
		await flushPromises()

		expect(showError).toHaveBeenCalledWith('Failed to load accounts')
		expect(wrapper.text()).toContain('No accounts')
	})
})
