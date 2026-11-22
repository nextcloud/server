/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { emit } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { cleanup, render } from '@testing-library/vue'
import { createPinia } from 'pinia'
import { afterEach, describe, expect, it, vi } from 'vitest'
import UserStatus from './UserStatus.vue'
import { useUserStatusStore } from './store/userStatus.ts'

vi.mock('@nextcloud/auth')
vi.mock('@nextcloud/initial-state', () => ({ loadState: vi.fn() }))

describe('UserStatus', () => {
	afterEach(cleanup)

	it('shows the status from the initial state', async () => {
		vi.mocked(loadState).mockReturnValue({
			status: 'away',
			statusIsUserDefined: true,
			message: 'In a meeting',
			icon: '📅',
			clearAt: null,
			messageIsPredefined: false,
			messageId: null,
		})

		const component = render(UserStatus, {
			props: { inline: true },
			global: { plugins: [createPinia()] },
		})

		expect(await component.findByRole('button', { name: '📅 In a meeting' })).toBeTruthy()
	})

	it('updates the shared store from status events of the current user', async () => {
		vi.mocked(loadState).mockReturnValue({ status: 'online', statusIsUserDefined: true, message: null, icon: null })

		const pinia = createPinia()
		const component = render(UserStatus, {
			props: { inline: true },
			global: { plugins: [pinia] },
		})
		expect(await component.findByRole('button', { name: 'Online' })).toBeTruthy()

		emit('user_status:status.updated', { userId: 'other', status: 'dnd', message: 'Other user', icon: null })
		emit('user_status:status.updated', { userId: 'test', status: 'dnd', message: null, icon: null })

		expect(await component.findByRole('button', { name: 'Do not disturb' })).toBeTruthy()
		expect(useUserStatusStore(pinia).status).toBe('dnd')
	})
})
