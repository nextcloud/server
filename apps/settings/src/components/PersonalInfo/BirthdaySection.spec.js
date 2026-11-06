/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { afterEach, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest'
import { nextTick } from 'vue'

let personalInfoParameters
vi.mock('@nextcloud/initial-state', () => ({
	loadState(app, key, fallback) {
		if (app === 'settings' && key === 'personalInfoParameters' && personalInfoParameters !== undefined) {
			return personalInfoParameters
		}
		if (fallback !== undefined) {
			return fallback
		}

		console.error('Unexpected loadState call without fallback', { app, key })
		throw new Error()
	},
}))

const savePrimaryAccountProperty = vi.hoisted(() => vi.fn())
vi.mock('../../service/PersonalInfo/PersonalInfoService.js', () => ({
	savePrimaryAccountProperty,
}))

async function mountBirthdaySection() {
	const BirthdaySection = await import('./BirthdaySection.vue')
	return mount(BirthdaySection.default, {
		mocks: {
			t: (_app, text) => text,
		},
	})
}

// The component reads its initial state at module scope, so every test has to import it freshly.
// Transforming its module graph once up front keeps that import out of the tests' timeout budget.
beforeAll(async () => {
	await import('./BirthdaySection.vue')
})

beforeEach(() => {
	vi.resetModules()
	savePrimaryAccountProperty.mockClear()
})

afterEach(() => {
	vi.unstubAllEnvs()
	personalInfoParameters = undefined
})

describe('BirthdaySection', () => {
	it('saves value', async () => {
		personalInfoParameters = {
			birthdate: {
				name: 'birthdate',
				value: null,
			},
		}
		savePrimaryAccountProperty.mockReturnValue(Promise.resolve({
			ocs: { meta: { status: 'ok' } },
		}))
		const wrapper = await mountBirthdaySection()

		const input = wrapper.find('input')
		await input.setValue('1987-12-01')

		await expect.poll(() => savePrimaryAccountProperty.mock.calls.length).toBe(1)
		expect(savePrimaryAccountProperty).toHaveBeenCalledWith(
			'birthdate',
			'1987-12-01T00:00:00.000Z',
		)
		expect(input.element.value).toBe('1987-12-01')
	})

	it('displays value when browser timezone is set', async () => {
		vi.stubEnv('TZ', 'US/Pacific')
		personalInfoParameters = {
			birthdate: {
				name: 'birthdate',
				value: '1987-12-15T00:00:00.000Z',
			},
		}

		const wrapper = await mountBirthdaySection()

		expect(wrapper.find('input').element.value).toBe('1987-12-15')
	})

	it('saves value when browser timezone is set', async () => {
		vi.stubEnv('TZ', 'US/Pacific')
		personalInfoParameters = {
			birthdate: {
				name: 'birthdate',
				value: null,
			},
		}
		savePrimaryAccountProperty.mockReturnValue(Promise.resolve({
			ocs: { meta: { status: 'ok' } },
		}))
		const wrapper = await mountBirthdaySection()

		const input = wrapper.find('input')
		await input.setValue('1987-12-01')

		await expect.poll(() => savePrimaryAccountProperty.mock.calls.length).toBe(1)
		expect(savePrimaryAccountProperty).toHaveBeenCalledWith(
			'birthdate',
			'1987-12-01T00:00:00.000Z',
		)
		expect(input.element.value).toBe('1987-12-01')
	})

	it('ignores null value from partially filled picker and does not save', async () => {
		personalInfoParameters = {
			birthdate: {
				name: 'birthdate',
				value: '1987-12-15T00:00:00.000Z',
			},
		}
		const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {})
		const wrapper = await mountBirthdaySection()

		const picker = wrapper.findComponent({ name: 'NcDateTimePickerNative' })
		picker.vm.$emit('update:modelValue', null)
		await nextTick()

		await new Promise((resolve) => setTimeout(resolve, 550))
		expect(savePrimaryAccountProperty).not.toHaveBeenCalled()
		expect(wrapper.find('input').element.value).toBe('1987-12-15')
		expect(consoleError).not.toHaveBeenCalled()
		consoleError.mockRestore()
	})

	it('saves value emitted on update:modelValue', async () => {
		personalInfoParameters = {
			birthdate: {
				name: 'birthdate',
				value: null,
			},
		}
		savePrimaryAccountProperty.mockReturnValue(Promise.resolve({
			ocs: { meta: { status: 'ok' } },
		}))
		const wrapper = await mountBirthdaySection()

		const picker = wrapper.findComponent({ name: 'NcDateTimePickerNative' })
		picker.vm.$emit('update:modelValue', new Date(1988, 2, 20))
		await nextTick()

		await expect.poll(() => savePrimaryAccountProperty.mock.calls.length).toBe(1)
		expect(savePrimaryAccountProperty).toHaveBeenCalledWith(
			'birthdate',
			'1988-03-20T00:00:00.000Z',
		)
		expect(wrapper.find('input').element.value).toBe('1988-03-20')
	})

	it('does not throw or save on partially typed dates', async () => {
		personalInfoParameters = {
			birthdate: {
				name: 'birthdate',
				value: null,
			},
		}
		const wrapper = await mountBirthdaySection()

		const input = wrapper.find('input')
		await input.setValue('')

		await new Promise((resolve) => setTimeout(resolve, 550))
		expect(savePrimaryAccountProperty).not.toHaveBeenCalled()
		expect(wrapper.vm.birthdate.value).toBe(null)
	})

	it('settles a burst of picker updates into a single save of the final date', async () => {
		personalInfoParameters = {
			birthdate: {
				name: 'birthdate',
				value: null,
			},
		}
		savePrimaryAccountProperty.mockReturnValue(Promise.resolve({
			ocs: { meta: { status: 'ok' } },
		}))
		const wrapper = await mountBirthdaySection()

		// Chrome completes the date while the year is still being typed
		const picker = wrapper.findComponent({ name: 'NcDateTimePickerNative' })
		for (const date of [new Date(2000, 11, 31), new Date(2031, 8, 11), new Date(2011, 7, 8), new Date(2008, 6, 7)]) {
			picker.vm.$emit('update:modelValue', date)
			await new Promise((resolve) => setTimeout(resolve, 50))
		}
		expect(wrapper.vm.birthdate.value).toBe(null)

		await new Promise((resolve) => setTimeout(resolve, 550))
		expect(savePrimaryAccountProperty).toHaveBeenCalledTimes(1)
		expect(savePrimaryAccountProperty).toHaveBeenCalledWith(
			'birthdate',
			'2008-07-07T00:00:00.000Z',
		)
		expect(wrapper.vm.birthdate.value).toBe('2008-07-07')
		expect(wrapper.find('input').element.value).toBe('2008-07-07')
	})

	it('renders an empty picker for a null birth date', async () => {
		personalInfoParameters = {
			birthdate: {
				name: 'birthdate',
				value: null,
			},
		}
		const wrapper = await mountBirthdaySection()

		const picker = wrapper.findComponent({ name: 'NcDateTimePickerNative' })
		expect(picker.props('modelValue')).toBe(null)
		expect(wrapper.find('input').element.value).toBe('')
	})

	it('renders an empty picker for an unparseable birth date', async () => {
		personalInfoParameters = {
			birthdate: {
				name: 'birthdate',
				value: 'not-a-date',
			},
		}
		const wrapper = await mountBirthdaySection()

		const picker = wrapper.findComponent({ name: 'NcDateTimePickerNative' })
		expect(picker.props('modelValue')).toBe(null)
		expect(wrapper.find('input').element.value).toBe('')
	})

	it('ignores dates clamped from overflowing year segments', async () => {
		personalInfoParameters = {
			birthdate: {
				name: 'birthdate',
				value: null,
			},
		}
		const wrapper = await mountBirthdaySection()

		const picker = wrapper.findComponent({ name: 'NcDateTimePickerNative' })
		picker.vm.$emit('update:modelValue', new Date(8640000000000000))
		await nextTick()

		await new Promise((resolve) => setTimeout(resolve, 550))
		expect(savePrimaryAccountProperty).not.toHaveBeenCalled()
		expect(wrapper.vm.birthdate.value).toBe(null)
	})

	it('saves dates at the lower and upper year boundary', async () => {
		personalInfoParameters = {
			birthdate: {
				name: 'birthdate',
				value: null,
			},
		}
		savePrimaryAccountProperty.mockReturnValue(Promise.resolve({
			ocs: { meta: { status: 'ok' } },
		}))
		const wrapper = await mountBirthdaySection()

		const picker = wrapper.findComponent({ name: 'NcDateTimePickerNative' })
		// the Date constructor maps years 0-99 to 19xx, 100 is the lowest testable year
		picker.vm.$emit('update:modelValue', new Date(100, 0, 1))
		await expect.poll(() => savePrimaryAccountProperty.mock.calls.length).toBe(1)
		expect(savePrimaryAccountProperty).toHaveBeenCalledWith(
			'birthdate',
			'0100-01-01T00:00:00.000Z',
		)

		picker.vm.$emit('update:modelValue', new Date(9999, 11, 31))
		await expect.poll(() => savePrimaryAccountProperty.mock.calls.length).toBe(2)
		expect(savePrimaryAccountProperty).toHaveBeenCalledWith(
			'birthdate',
			'9999-12-31T00:00:00.000Z',
		)
		expect(wrapper.vm.birthdate.value).toBe('9999-12-31')
	})

	it('limits the picker to years 1 to 9999', async () => {
		personalInfoParameters = {
			birthdate: {
				name: 'birthdate',
				value: null,
			},
		}
		const wrapper = await mountBirthdaySection()

		const input = wrapper.find('input')
		expect(input.attributes('min')).toBe('0001-01-01')
		expect(input.attributes('max')).toBe('9999-12-31')
	})
})
