/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { UserAction } from '../../../../views/user-types.d.ts'

import { createTestingPinia } from '@pinia/testing'
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { h } from 'vue'
import UserRow from './UserRow.vue'

const mocks = vi.hoisted(() => ({
	confirmPassword: vi.fn(),
	confirmDestructive: vi.fn(),
}))

vi.mock('@nextcloud/password-confirmation', () => ({ confirmPassword: mocks.confirmPassword }))
vi.mock('@nextcloud/auth', async (importActual) => ({
	...(await importActual<object>()),
	getCurrentUser: () => ({ uid: 'admin' }),
}))
vi.mock('./UserRowActions.vue', () => ({
	default: { name: 'UserRowActions', props: ['actions', 'disabled', 'user'], render: () => h('div') },
}))

/**
 * Mount the row of a plain account, as seen by an admin
 */
function mountRow() {
	return mount(UserRow, {
		attachTo: document.createElement('tbody'),
		props: {
			user: {
				id: 'bob',
				displayname: 'Bob',
				groups: [],
				subadmin: [],
				quota: { quota: 'default', used: 0 },
				enabled: true,
				language: '',
				firstLoginTimestamp: 0,
				lastLoginTimestamp: 0,
				backendCapabilities: {},
			},
			visible: true,
			users: [],
			quotaOptions: [],
			languages: [{ languages: [] }, { languages: [] }],
			settings: { isAdmin: true, defaultQuota: 'none' },
		},
		global: { plugins: [createTestingPinia({ createSpy: vi.fn })] },
	})
}

/**
 * @param wrapper The mounted row
 * @param text The action label
 */
function runAction(wrapper: ReturnType<typeof mountRow>, text: string) {
	const actions: UserAction[] = wrapper.findComponent({ name: 'UserRowActions' }).props('actions')
	return actions.find((action) => action.text === text)!.action()
}

describe('UserRow', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		window.OC = {
			...window.OC,
			theme: { productName: 'Nextcloud' },
			dialogs: { confirmDestructive: mocks.confirmDestructive, YES_NO_BUTTONS: 70 },
		}
	})

	it.each([
		'Delete account',
		'Disconnect all devices and delete local data',
	])('asks for the password before "%s"', async (text) => {
		mocks.confirmPassword.mockResolvedValue(undefined)
		const wrapper = mountRow()

		await runAction(wrapper, text)
		await flushPromises()

		expect(mocks.confirmPassword).toHaveBeenCalledOnce()
		expect(mocks.confirmDestructive).toHaveBeenCalled()
		expect(mocks.confirmPassword.mock.invocationCallOrder[0]).toBeLessThan(mocks.confirmDestructive.mock.invocationCallOrder[0]!)
	})

	it.each([
		'Delete account',
		'Disconnect all devices and delete local data',
	])('stops "%s" when the password dialog is cancelled', async (text) => {
		mocks.confirmPassword.mockRejectedValue(new Error('cancelled'))
		const wrapper = mountRow()

		await expect(runAction(wrapper, text)).resolves.toBeUndefined()
		expect(mocks.confirmDestructive).not.toHaveBeenCalled()
	})
})
