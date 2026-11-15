/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { h } from 'vue'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import AccountPropertySection from './AccountPropertySection.vue'

const mocks = vi.hoisted(() => ({
	savePrimaryAccountProperty: vi.fn(),
	handleError: vi.fn(),
}))

vi.mock('../../../../service/PersonalInfo/PersonalInfoService.js', () => ({
	savePrimaryAccountProperty: mocks.savePrimaryAccountProperty,
}))
vi.mock('../../../../utils/handlers.ts', () => ({ handleError: mocks.handleError }))
vi.mock('./VisibilityScopeControl.vue', () => ({
	default: { name: 'VisibilityScopeControl', render: () => h('div') },
}))

/**
 * Mount the section with a parent that owns the value but has not re-rendered yet
 *
 * @param props Extra props of the section
 */
function mountSection(props = {}) {
	return mount(AccountPropertySection, {
		props: { name: 'phone', readable: 'Phone number', placeholder: '', value: '+1 555', 'onUpdate:value': () => {}, scope: 'v2-local', ...props },
	})
}

describe('AccountPropertySection', () => {
	beforeEach(() => {
		vi.useFakeTimers()
		mocks.savePrimaryAccountProperty.mockReset()
		mocks.handleError.mockReset()
	})

	afterEach(() => {
		vi.useRealTimers()
	})

	it('saves the entered value, not the previous one', async () => {
		mocks.savePrimaryAccountProperty.mockResolvedValue({ ocs: { meta: { status: 'ok' } } })
		const onSave = vi.fn()
		const wrapper = mountSection({ onSave })

		await wrapper.find('input').setValue(' +1 777 ')
		await vi.advanceTimersByTimeAsync(1000)

		expect(mocks.savePrimaryAccountProperty).toHaveBeenCalledExactlyOnceWith('phone', '+1 777')
		expect(onSave).toHaveBeenCalledWith('+1 777')
		expect(wrapper.emitted('update:value')).toEqual([[' +1 777 ']])
	})

	it('keeps the error while the field holds an unsaved value', async () => {
		mocks.savePrimaryAccountProperty.mockRejectedValue(new Error('500'))
		const wrapper = mountSection()

		await wrapper.find('input').setValue('+1 777')
		await vi.advanceTimersByTimeAsync(5000)

		expect(mocks.handleError).toHaveBeenCalled()
		expect(wrapper.findComponent(NcInputField).props('error')).toBe(true)
	})

	it('does not save an invalid value', async () => {
		const wrapper = mountSection({ onValidate: () => false })

		await wrapper.find('input').setValue('nope')
		await vi.advanceTimersByTimeAsync(1000)

		expect(mocks.savePrimaryAccountProperty).not.toHaveBeenCalled()
		expect(wrapper.findComponent(NcInputField).props('helperText')).toBe('Invalid value')
	})
})
