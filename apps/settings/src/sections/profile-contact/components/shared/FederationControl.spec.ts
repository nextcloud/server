/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import FederationControl from './FederationControl.vue'

// The settings constants reference these l10n helpers as build-provided globals.
vi.hoisted(() => {
	globalThis.t = (app: string, text: string) => text
	globalThis.n = (app: string, singular: string, plural: string, count: number) => (count === 1 ? singular : plural)
})

const mocks = vi.hoisted(() => ({
	savePrimaryAccountPropertyScope: vi.fn(),
	handleError: vi.fn(),
}))

vi.mock('@nextcloud/initial-state', () => ({ loadState: (app: string, key: string, fallback: unknown) => fallback }))
vi.mock('../../../../service/PersonalInfo/PersonalInfoService.js', () => ({
	savePrimaryAccountPropertyScope: mocks.savePrimaryAccountPropertyScope,
}))
vi.mock('../../../../utils/handlers.ts', () => ({ handleError: mocks.handleError }))

const flushPromises = () => new Promise((resolve) => setTimeout(resolve))

/**
 * Mount the control for the phone number, saved with the local scope
 */
function mountControl() {
	return mount(FederationControl, {
		props: { readable: 'Phone number', scope: 'v2-local' },
		global: {
			stubs: {
				NcActions: { template: '<div><slot /></div>' },
				NcActionButton: {
					props: ['value'],
					template: '<button :data-scope="value" @click="$emit(\'update:modelValue\', value)"><slot /></button>',
				},
			},
		},
	})
}

/**
 * Mount the control for the phone number and pick the private scope
 */
async function pickPrivateScope() {
	const wrapper = mountControl()
	await wrapper.find('[data-scope="v2-private"]').trigger('click')
	await flushPromises()
	return wrapper
}

describe('FederationControl', () => {
	beforeEach(() => {
		mocks.savePrimaryAccountPropertyScope.mockReset()
		mocks.handleError.mockReset()
	})

	it('saves the picked scope', async () => {
		mocks.savePrimaryAccountPropertyScope.mockResolvedValue({ ocs: { meta: { status: 'ok' } } })
		const wrapper = await pickPrivateScope()

		expect(mocks.savePrimaryAccountPropertyScope).toHaveBeenCalledWith('phone', 'v2-private')
		expect(wrapper.emitted('update:scope')).toEqual([['v2-private']])
	})

	it('resets the scope when saving fails', async () => {
		mocks.savePrimaryAccountPropertyScope.mockRejectedValue(new Error('500'))
		const wrapper = await pickPrivateScope()

		expect(mocks.handleError).toHaveBeenCalled()
		expect(wrapper.emitted('update:scope')).toEqual([['v2-private'], ['v2-local']])
	})

	it('resets to the saved scope when a quick second change fails', async () => {
		let resolveFirst!: (value: unknown) => void
		mocks.savePrimaryAccountPropertyScope
			.mockReturnValueOnce(new Promise((resolve) => {
				resolveFirst = resolve
			}))
			.mockRejectedValueOnce(new Error('500'))
		const wrapper = mountControl()

		await wrapper.find('[data-scope="v2-private"]').trigger('click')
		await wrapper.setProps({ scope: 'v2-private' })
		await wrapper.find('[data-scope="v2-local"]').trigger('click')
		await flushPromises()

		// the first change is not saved yet, so local is still the saved scope
		expect(wrapper.emitted('update:scope')!.at(-1)).toEqual(['v2-local'])
		resolveFirst({ ocs: { meta: { status: 'ok' } } })
	})
})
