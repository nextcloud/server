/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { flushPromises, mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import BackupCodesDialog from '../components/BackupCodesDialog.vue'
import PersonalSettings from './PersonalSettings.vue'
import { useStore } from '../store/index.ts'

vi.mock('@nextcloud/initial-state', () => ({
	loadState: () => ({ enabled: true, total: 10, used: 3 }),
}))

vi.mock('@nextcloud/password-confirmation', () => ({
	confirmPassword: vi.fn(() => Promise.resolve()),
}))

function mountSettings() {
	return mount(PersonalSettings, {
		global: {
			plugins: [createTestingPinia({ createSpy: vi.fn, stubActions: false })],
			stubs: { BackupCodesDialog: true },
		},
	})
}

describe('Backup codes settings', () => {
	it('shows how many codes are used', () => {
		const wrapper = mountSettings()

		expect(wrapper.text()).toContain('Regenerate backup codes')
		expect(wrapper.text()).toContain('3 out of the 10 current codes have been used')
	})

	it('offers generating codes before any exist', async () => {
		const wrapper = mountSettings()
		useStore().enabled = false
		await flushPromises()

		expect(wrapper.text()).toContain('Generate backup codes')
		expect(wrapper.text()).not.toContain('Regenerate')
		expect(wrapper.text()).not.toContain('have been used')
	})

	it('ignores clicks while codes are being generated', async () => {
		const wrapper = mountSettings()
		const store = useStore()
		vi.mocked(store.generate).mockImplementation(() => new Promise(() => {}))

		await wrapper.find('button').trigger('click')
		await flushPromises()
		await wrapper.find('button').trigger('click')
		await flushPromises()

		expect(store.generate).toHaveBeenCalledTimes(1)
	})

	it('shows the new codes in a dialog and forgets them once it closes', async () => {
		const wrapper = mountSettings()
		const store = useStore()
		vi.mocked(store.generate).mockImplementation(async () => {
			store.codes = ['code1', 'code2']
		})

		await wrapper.find('button').trigger('click')
		await flushPromises()

		const dialog = wrapper.findComponent(BackupCodesDialog)
		expect(dialog.props('codes')).toEqual(['code1', 'code2'])

		dialog.vm.$emit('close')
		await flushPromises()

		expect(store.codes).toEqual([])
		expect(wrapper.findComponent(BackupCodesDialog).exists()).toBe(false)
	})
})
