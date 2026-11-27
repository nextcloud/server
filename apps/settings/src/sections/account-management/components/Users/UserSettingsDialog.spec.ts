/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import UserSettingsDialog from './UserSettingsDialog.vue'
import { useUsersStore } from '../../store/users.ts'

const mocks = vi.hoisted(() => ({
	post: vi.fn(),
	showError: vi.fn(),
}))

vi.mock('@nextcloud/axios', () => ({ default: { post: mocks.post } }))
vi.mock('@nextcloud/dialogs', () => ({ showError: mocks.showError }))

/**
 * Mount the open dialog with welcome emails on and a 1 GB default quota
 */
function mountDialog() {
	return mount(UserSettingsDialog, {
		props: { open: true },
		global: {
			plugins: [createTestingPinia({
				createSpy: vi.fn,
				initialState: {
					'settings-users': {
						serverData: { newUserSendEmail: true, defaultQuota: '1 GB', quotaPreset: ['1 GB', '5 GB'], allowUnlimitedQuota: true },
						showConfig: {},
					},
				},
			})],
			stubs: {
				// The real dialog renders into a modal outside the wrapper, its sections need it
				NcAppSettingsDialog: true,
				NcAppSettingsSection: true,
				NcAppSettingsShortcutsSection: true,
			},
			renderStubDefaultSlot: true,
		},
	})
}

/**
 * @param wrapper The mounted dialog
 */
function welcomeMailSwitch(wrapper: ReturnType<typeof mountDialog>) {
	return wrapper.findAllComponents(NcCheckboxRadioSwitch).find((item) => item.text() === 'Send welcome email to new accounts')!
}

describe('UserSettingsDialog', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it('saves the welcome email choice', async () => {
		mocks.post.mockResolvedValue({})
		const wrapper = mountDialog()

		await welcomeMailSwitch(wrapper).vm.$emit('update:modelValue', false)
		await flushPromises()

		expect(mocks.post).toHaveBeenCalledWith(expect.stringContaining('newUser.sendEmail'), { value: 'no' })
		expect(useUsersStore().serverData.newUserSendEmail).toBe(false)
	})

	it('switches the welcome email back when saving fails', async () => {
		mocks.post.mockRejectedValue(new Error('500'))
		const wrapper = mountDialog()

		await welcomeMailSwitch(wrapper).vm.$emit('update:modelValue', false)
		await flushPromises()

		expect(mocks.showError).toHaveBeenCalled()
		expect(useUsersStore().serverData.newUserSendEmail).toBe(true)
		expect(welcomeMailSwitch(wrapper).props('modelValue')).toBe(true)
	})

	it('saves the picked default quota', async () => {
		const wrapper = mountDialog()
		const store = useUsersStore()
		vi.mocked(store.setAppConfig).mockResolvedValue({ data: {} })
		const select = wrapper.findComponent(NcSelect)

		await select.vm.$emit('update:modelValue', { id: '5 GB', label: '5 GB' })
		await select.vm.$emit('option:selected', { id: '5 GB', label: '5 GB' })
		await flushPromises()

		expect(store.setAppConfig).toHaveBeenCalledWith({ app: 'files', key: 'default_quota', value: '5 GB' })
		expect(select.props('modelValue')).toEqual({ id: '5 GB', label: '5 GB' })
		expect(store.serverData.defaultQuota).toBe('5 GB')
	})

	it('goes back to the saved default quota when saving fails', async () => {
		const wrapper = mountDialog()
		const store = useUsersStore()
		// The store shows the error and resolves without a response
		vi.mocked(store.setAppConfig).mockResolvedValue(undefined)
		const select = wrapper.findComponent(NcSelect)

		await select.vm.$emit('update:modelValue', { id: '5 GB', label: '5 GB' })
		await select.vm.$emit('option:selected', { id: '5 GB', label: '5 GB' })
		await flushPromises()

		expect(select.props('modelValue')).toEqual({ id: '1 GB', label: '1 GB' })
		expect(store.serverData.defaultQuota).toBe('1 GB')
	})
})
