/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { h } from 'vue'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import EmailSectionEntry from './EmailSectionEntry.vue'

// The settings constants reference these l10n helpers as build-provided globals.
vi.hoisted(() => {
	globalThis.t = (app: string, text: string) => text
	globalThis.n = (app: string, singular: string, plural: string, count: number) => (count === 1 ? singular : plural)
})

const mocks = vi.hoisted(() => ({
	savePrimaryEmail: vi.fn(),
	saveAdditionalEmail: vi.fn(),
	removeAdditionalEmail: vi.fn(),
	handleError: vi.fn(),
}))

vi.mock('../../../../service/PersonalInfo/EmailService.js', () => ({
	removeAdditionalEmail: mocks.removeAdditionalEmail,
	saveAdditionalEmail: mocks.saveAdditionalEmail,
	saveAdditionalEmailScope: vi.fn(),
	saveNotificationEmail: vi.fn(),
	savePrimaryEmail: mocks.savePrimaryEmail,
	updateAdditionalEmail: vi.fn(),
}))
vi.mock('../../../../utils/handlers.ts', () => ({ handleError: mocks.handleError }))
vi.mock('../shared/FederationControl.vue', () => ({
	default: { name: 'FederationControl', props: ['additionalValue'], render() { return h('div', { class: 'federation-control' }, this.$slots.default?.()) } },
}))

const flushPromises = () => new Promise((resolve) => setTimeout(resolve))

/**
 * Mount the primary entry and click "Remove primary email"
 */
async function removePrimaryEmail() {
	const wrapper = mount(EmailSectionEntry, {
		props: { email: 'primary@example.com', scope: 'v2-federated', primary: true },
		global: {
			stubs: {
				NcActionButton: { emits: ['click'], template: '<button @click="$emit(\'click\')"><slot /></button>' },
			},
		},
	})
	const button = wrapper.findAll('button').find((b) => b.text() === 'Remove primary email')!
	await button.trigger('click')
	await flushPromises()
	return wrapper
}

describe('EmailSectionEntry', () => {
	afterEach(() => {
		vi.useRealTimers()
	})

	beforeEach(() => {
		mocks.savePrimaryEmail.mockReset()
		mocks.saveAdditionalEmail.mockReset()
		mocks.removeAdditionalEmail.mockReset()
		mocks.handleError.mockReset()
	})

	it('clears the primary address once the server removed it', async () => {
		mocks.savePrimaryEmail.mockResolvedValue({ ocs: { meta: { status: 'ok' } } })
		const wrapper = await removePrimaryEmail()

		expect(mocks.savePrimaryEmail).toHaveBeenCalledWith('')
		expect(wrapper.emitted('update:email')).toEqual([['']])
		expect(wrapper.emitted('deletePrimaryEmail')).toHaveLength(1)
		// the parent promotes an additional address into this entry, so its options must stay
		expect(wrapper.find('.federation-control').exists()).toBe(true)
	})

	it('keeps the primary address when removing it fails', async () => {
		mocks.savePrimaryEmail.mockRejectedValue(new Error('500'))
		const wrapper = await removePrimaryEmail()

		expect(mocks.handleError).toHaveBeenCalled()
		expect(wrapper.emitted('update:email')).toBeUndefined()
		expect(wrapper.emitted('deletePrimaryEmail')).toBeUndefined()
		expect(wrapper.find('input').element.value).toBe('primary@example.com')
	})

	it('keeps the error while the field holds an unsaved address', async () => {
		vi.useFakeTimers()
		mocks.savePrimaryEmail.mockRejectedValue(new Error('500'))
		const wrapper = mount(EmailSectionEntry, {
			props: { email: 'primary@example.com', scope: 'v2-federated', primary: true },
		})

		await wrapper.find('input').setValue('new@example.com')
		await vi.advanceTimersByTimeAsync(5000)

		expect(mocks.savePrimaryEmail).toHaveBeenCalledWith('new@example.com')
		expect(wrapper.findComponent(NcTextField).props('error')).toBe(true)
	})

	it('deletes an added address by the lower-cased value the server stored', async () => {
		vi.useFakeTimers()
		mocks.saveAdditionalEmail.mockResolvedValue({ ocs: { meta: { status: 'ok' } } })
		mocks.removeAdditionalEmail.mockResolvedValue({ ocs: { meta: { status: 'ok' } } })
		const wrapper = mount(EmailSectionEntry, {
			props: { email: '', scope: 'v2-local' },
			global: {
				stubs: {
					NcActionButton: { emits: ['click'], template: '<button @click="$emit(\'click\')"><slot /></button>' },
				},
			},
		})

		await wrapper.find('input').setValue('Second@Example.com')
		await vi.advanceTimersByTimeAsync(1000)
		expect(mocks.saveAdditionalEmail).toHaveBeenCalledWith('Second@Example.com')
		expect(wrapper.find('input').element.value).toBe('second@example.com')

		await wrapper.findAll('button').find((b) => b.text() === 'Delete email')!.trigger('click')
		await vi.advanceTimersByTimeAsync(0)
		expect(mocks.removeAdditionalEmail).toHaveBeenCalledWith('second@example.com')
	})

	it('changes the scope of the saved address while an edit is pending', async () => {
		const wrapper = mount(EmailSectionEntry, {
			props: { email: 'second@example.com', scope: 'v2-local' },
		})

		await wrapper.find('input').setValue('third@example.com')
		const control = wrapper.findComponent({ name: 'FederationControl' })
		expect(control.props('additionalValue')).toBe('second@example.com')
	})
})
