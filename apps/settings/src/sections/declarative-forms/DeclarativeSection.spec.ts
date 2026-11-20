/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { DeclarativeForm, DeclarativeFormField } from './types.ts'

import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import DeclarativeSection from './DeclarativeSection.vue'

const mocks = vi.hoisted(() => ({
	post: vi.fn(),
	showError: vi.fn(),
	confirmPassword: vi.fn(),
}))

vi.mock('@nextcloud/axios', () => ({ default: { post: mocks.post }, isAxiosError: () => false }))
vi.mock('@nextcloud/dialogs', () => ({ showError: mocks.showError }))
vi.mock('@nextcloud/password-confirmation', () => ({ confirmPassword: mocks.confirmPassword }))

/**
 * @param fields The fields of the form
 * @param id Id of the form
 */
function mountForm(fields: DeclarativeFormField[], id = 'test_form') {
	const form: DeclarativeForm = {
		id,
		app: 'testing',
		priority: 10,
		section_type: 'admin',
		section_id: 'additional',
		storage_type: 'internal',
		title: 'Test form',
		fields,
	}
	return mount(DeclarativeSection, { props: { form } })
}

/**
 * A radio field that is set to hourly
 */
function radioField(): DeclarativeFormField {
	return {
		id: 'interval',
		title: 'Interval',
		type: 'radio',
		options: [{ name: 'Hourly', value: '1h' }, { name: 'Daily', value: '1d' }],
		value: '1h',
	}
}

/**
 * The payload of the last save request
 */
function lastSaved() {
	return mocks.post.mock.lastCall?.[1]
}

describe('DeclarativeSection', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		mocks.post.mockResolvedValue({})
		mocks.confirmPassword.mockResolvedValue(undefined)
	})

	afterEach(() => {
		vi.useRealTimers()
	})

	it('saves a switch as a number', async () => {
		const wrapper = mountForm([{ id: 'enabled', title: 'Enabled', type: 'checkbox', value: '0' }])

		await wrapper.findComponent(NcCheckboxRadioSwitch).vm.$emit('update:modelValue', true)
		await flushPromises()

		expect(mocks.confirmPassword).not.toHaveBeenCalled()
		expect(mocks.post.mock.lastCall?.[0]).toMatch(/settings\/api\/declarative\/value$/)
		expect(lastSaved()).toEqual({ app: 'testing', formId: 'test_form', fieldId: 'enabled', value: 1 })
	})

	it('describes a switch with the field description', () => {
		const wrapper = mountForm([{ id: 'enabled', title: 'Enabled', description: 'Turns it on', type: 'checkbox', value: '0' }])
		const describedBy = wrapper.find('input[type="checkbox"]').attributes('aria-describedby')

		expect(wrapper.find(`[id="${describedBy}"]`).text()).toBe('Turns it on')
	})

	it.each([
		['with its own label', 'Turn it on', 'Enabled'],
		['without its own label', undefined, undefined],
	])('names the switch group only for a switch %s', (_, label, groupName) => {
		const wrapper = mountForm([{ id: 'enabled', title: 'Enabled', label, type: 'checkbox', value: '0' }])

		expect(wrapper.find('fieldset legend').exists() ? wrapper.find('fieldset legend').text() : undefined).toBe(groupName)
	})

	it('saves a multi-select as a JSON list', async () => {
		const wrapper = mountForm([
			{ id: 'tags', title: 'Tags', type: 'multi-select', options: ['foo', 'bar'], value: '["foo"]' },
			{ id: 'empty', title: 'Empty', type: 'multi-select', options: ['foo', 'bar'], value: '' },
		])
		const [tags, empty] = wrapper.findAllComponents(NcSelect)

		expect(empty!.props('modelValue')).toEqual([])
		await tags!.vm.$emit('update:modelValue', ['foo', 'bar'])
		await flushPromises()

		expect(lastSaved().value).toBe('["foo","bar"]')
	})

	it('sends the form id as the app registered it', async () => {
		const wrapper = mountForm([{ id: 'enabled', title: 'Enabled', type: 'checkbox', value: '0' }], 'testing_admin')

		await wrapper.findComponent(NcCheckboxRadioSwitch).vm.$emit('update:modelValue', true)
		await flushPromises()

		expect(lastSaved().formId).toBe('testing_admin')
	})

	it('keeps checked options of a group that are still offered', async () => {
		const wrapper = mountForm([{
			id: 'pets',
			title: 'Pets',
			type: 'multi-checkbox',
			options: [{ name: 'Cat', value: 'cat' }, { name: 'Dog', value: 'dog' }],
			value: JSON.stringify({ cat: true, fish: true }),
		}])
		const [cat, dog] = wrapper.findAllComponents(NcCheckboxRadioSwitch)

		expect(cat!.props('modelValue')).toBe(true)
		expect(dog!.props('modelValue')).toBe(false)

		await dog!.vm.$emit('update:modelValue', true)
		await flushPromises()

		expect(JSON.parse(lastSaved().value)).toEqual({ cat: true, dog: true })
	})

	it('saves each text field, even when typing in another one right after', async () => {
		vi.useFakeTimers()
		const wrapper = mountForm([
			{ id: 'first', title: 'First', type: 'text', value: '' },
			{ id: 'second', title: 'Second', type: 'text', value: '' },
		])
		const [first, second] = wrapper.findAllComponents(NcInputField)

		await first!.vm.$emit('update:modelValue', 'o')
		await first!.vm.$emit('update:modelValue', 'one')
		await second!.vm.$emit('update:modelValue', 'two')
		expect(mocks.post).not.toHaveBeenCalled()
		await vi.advanceTimersByTimeAsync(1000)

		expect(mocks.post.mock.calls.map(([, data]) => [data.fieldId, data.value])).toEqual([['first', 'one'], ['second', 'two']])
	})

	it('goes back to the saved choice when saving fails', async () => {
		mocks.post.mockRejectedValue(new Error('500'))
		const wrapper = mountForm([radioField()])
		const daily = wrapper.findAllComponents(NcCheckboxRadioSwitch)[1]!

		await daily.vm.$emit('update:modelValue', '1d')
		await flushPromises()

		expect(mocks.showError).toHaveBeenCalledWith('Failed to save setting')
		expect(daily.props('modelValue')).toBe('1h')
	})

	it('goes back to the last successful choice, not the initial one', async () => {
		mocks.post.mockResolvedValueOnce({}).mockRejectedValueOnce(new Error('500'))
		const wrapper = mountForm([radioField()])
		const [hourly, daily] = wrapper.findAllComponents(NcCheckboxRadioSwitch)

		await daily!.vm.$emit('update:modelValue', '1d')
		await flushPromises()
		await hourly!.vm.$emit('update:modelValue', '1h')
		await flushPromises()

		expect(daily!.props('modelValue')).toBe('1d')
	})

	it('keeps a newer choice when an older save fails', async () => {
		let failFirst: (error: Error) => void = () => {}
		mocks.post
			.mockReturnValueOnce(new Promise((resolve, reject) => {
				failFirst = reject
			}))
			.mockResolvedValueOnce({})
			.mockReturnValueOnce(new Promise(() => {}))
		const wrapper = mountForm([{ id: 'enabled', title: 'Enabled', type: 'checkbox', value: '0' }])
		const toggle = wrapper.findComponent(NcCheckboxRadioSwitch)

		// on fails late, off is saved, on again is still being saved
		await toggle.vm.$emit('update:modelValue', true)
		await toggle.vm.$emit('update:modelValue', false)
		await flushPromises()
		await toggle.vm.$emit('update:modelValue', true)
		failFirst(new Error('500'))
		await flushPromises()

		expect(toggle.props('modelValue')).toBe(true)
	})

	it('keeps entered text when saving fails', async () => {
		vi.useFakeTimers()
		mocks.post.mockRejectedValue(new Error('500'))
		const wrapper = mountForm([{ id: 'name', title: 'Name', type: 'text', value: 'old' }])
		const input = wrapper.findComponent(NcInputField)

		await input.vm.$emit('update:modelValue', 'new')
		await vi.advanceTimersByTimeAsync(1000)

		expect(mocks.showError).toHaveBeenCalledWith('Failed to save setting')
		expect(input.props('modelValue')).toBe('new')
	})

	it('does not save a sensitive field without the password', async () => {
		vi.useFakeTimers()
		mocks.confirmPassword.mockRejectedValue(new Error('cancelled'))
		const wrapper = mountForm([{ id: 'secret', title: 'Secret', type: 'password', sensitive: true, value: '' }])

		await wrapper.findComponent(NcInputField).vm.$emit('update:modelValue', 'top secret')
		await vi.advanceTimersByTimeAsync(1000)

		expect(mocks.post).not.toHaveBeenCalled()
		expect(mocks.showError).toHaveBeenCalledWith('Password confirmation is required')
	})

	it('saves a sensitive field through the password protected endpoint', async () => {
		vi.useFakeTimers()
		const wrapper = mountForm([{ id: 'secret', title: 'Secret', type: 'password', sensitive: true, value: '' }])

		await wrapper.findComponent(NcInputField).vm.$emit('update:modelValue', 'top secret')
		await vi.advanceTimersByTimeAsync(1000)

		expect(mocks.post).toHaveBeenCalledWith(expect.stringContaining('settings/api/declarative/value-sensitive'), expect.objectContaining({ value: 'top secret' }))
	})
})
