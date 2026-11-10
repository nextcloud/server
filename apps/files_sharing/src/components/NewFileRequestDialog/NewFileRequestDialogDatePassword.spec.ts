/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { flushPromises, shallowMount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDateTimePickerNative from '@nextcloud/vue/components/NcDateTimePickerNative'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NewFileRequestDialogDatePassword from './NewFileRequestDialogDatePassword.vue'

vi.mock('../../services/ConfigService.ts', () => ({
	default: class {
		defaultExpireDate = null
		defaultExpirationDate = null
		isDefaultExpireDateEnabled = false
		isDefaultExpireDateEnforced = false
		enableLinkPasswordByDefault = false
		enforcePasswordForPublicLink = false
	},
}))

vi.mock('../../utils/GeneratePassword.ts', () => ({
	default: vi.fn().mockResolvedValue('generated-password'),
}))

/**
 * @param props The props of the dialog step
 */
function mountStep(props: Record<string, unknown>) {
	return shallowMount(NewFileRequestDialogDatePassword, { props })
}

describe('NewFileRequestDialogDatePassword', () => {
	it('forwards the picked expiration date', () => {
		const wrapper = mountStep({ expirationDate: new Date('2026-01-10'), password: null })
		const date = new Date('2026-02-01')

		wrapper.findComponent(NcDateTimePickerNative).vm.$emit('update:modelValue', date)

		expect(wrapper.emitted('update:expirationDate')).toEqual([[date]])
	})

	it('forwards the typed password', () => {
		const wrapper = mountStep({ expirationDate: null, password: 'secret' })

		wrapper.findComponent(NcPasswordField).vm.$emit('update:modelValue', 'other-secret')

		expect(wrapper.emitted('update:password')).toEqual([['other-secret']])
	})

	it('shows a newly generated password', async () => {
		const wrapper = mountStep({ expirationDate: null, password: 'secret' })
		expect(wrapper.findComponent(NcPasswordField).props('visible')).toBe(false)

		wrapper.findComponent(NcButton).vm.$emit('click')
		await flushPromises()

		expect(wrapper.emitted('update:password')).toEqual([['generated-password']])
		expect(wrapper.findComponent(NcPasswordField).props('visible')).toBe(true)
	})
})
