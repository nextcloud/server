/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { h } from 'vue'
import EmailSection from './EmailSection.vue'

const mocks = vi.hoisted(() => ({
	removeAdditionalEmail: vi.fn(),
	savePrimaryEmail: vi.fn(),
	handleError: vi.fn(),
}))

vi.mock('@nextcloud/initial-state', () => ({
	loadState: (app: string, key: string) => (key === 'accountParameters'
		? { emailChangeSupported: true }
		: {
				emailMap: {
					primaryEmail: { name: 'email', value: 'primary@example.com', scope: 'v2-local' },
					additionalEmails: [{ value: 'second@example.com', scope: 'v2-local', locallyVerified: '1' }],
					notificationEmail: 'second@example.com',
				},
			}),
}))
vi.mock('../../../../service/PersonalInfo/EmailService.js', () => ({
	removeAdditionalEmail: mocks.removeAdditionalEmail,
	savePrimaryEmail: mocks.savePrimaryEmail,
}))
vi.mock('../../../../utils/handlers.ts', () => ({ handleError: mocks.handleError }))
vi.mock('./EmailSectionEntry.vue', () => ({
	default: {
		name: 'EmailSectionEntry',
		props: { email: String, primary: Boolean, activeNotificationEmail: String },
		emits: ['update:email', 'deletePrimaryEmail'],
		render() {
			return h('div', { class: this.primary ? 'primary' : 'additional' }, this.email)
		},
	},
}))

const flushPromises = () => new Promise((resolve) => setTimeout(resolve))

/**
 * Mount the section and let the primary entry report its address as removed
 */
async function removePrimaryEmail() {
	const wrapper = mount(EmailSection)
	const entry = wrapper.findComponent({ name: 'EmailSectionEntry' })
	entry.vm.$emit('update:email', '')
	entry.vm.$emit('deletePrimaryEmail')
	await flushPromises()
	return wrapper
}

describe('EmailSection', () => {
	beforeEach(() => {
		mocks.removeAdditionalEmail.mockReset()
		mocks.savePrimaryEmail.mockReset()
		mocks.handleError.mockReset()
	})

	it('promotes the first additional address when the primary one is removed', async () => {
		mocks.removeAdditionalEmail.mockResolvedValue({ ocs: { meta: { status: 'ok' } } })
		mocks.savePrimaryEmail.mockResolvedValue({ ocs: { meta: { status: 'ok' } } })
		const wrapper = mount(EmailSection)
		const entry = wrapper.findComponent({ name: 'EmailSectionEntry' })
		entry.vm.$emit('update:email', '')
		entry.vm.$emit('deletePrimaryEmail')
		await flushPromises()

		expect(mocks.removeAdditionalEmail).toHaveBeenCalledWith('second@example.com')
		expect(mocks.savePrimaryEmail).toHaveBeenCalledWith('second@example.com')
		expect(wrapper.find('.primary').text()).toBe('second@example.com')
		expect(wrapper.find('.additional').exists()).toBe(false)
		// the server notifies the primary address again
		expect(wrapper.findComponent({ name: 'EmailSectionEntry' }).props('activeNotificationEmail')).toBe('')
		// a fresh entry, so it knows the promoted address is saved
		expect(wrapper.findComponent({ name: 'EmailSectionEntry' }).vm).not.toBe(entry.vm)
	})

	it('does not promote while the primary address is being retyped', async () => {
		const wrapper = mount(EmailSection)
		wrapper.findComponent({ name: 'EmailSectionEntry' }).vm.$emit('update:email', '')
		await flushPromises()

		expect(mocks.removeAdditionalEmail).not.toHaveBeenCalled()
		expect(wrapper.find('.additional').text()).toBe('second@example.com')
	})

	it('keeps the additional address when removing it fails', async () => {
		mocks.removeAdditionalEmail.mockRejectedValue(new Error('500'))
		const wrapper = await removePrimaryEmail()

		expect(mocks.handleError).toHaveBeenCalled()
		expect(mocks.savePrimaryEmail).not.toHaveBeenCalled()
		expect(wrapper.find('.primary').text()).toBe('')
		expect(wrapper.find('.additional').text()).toBe('second@example.com')
	})
})
