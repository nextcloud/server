/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import VisibilityScopeControl from './VisibilityScopeControl.vue'

// The settings constants reference these l10n helpers as build-provided globals.
vi.hoisted(() => {
	globalThis.t = (app: string, text: string) => text
	globalThis.n = (app: string, singular: string, plural: string, count: number) => (count === 1 ? singular : plural)
})

const mocks = vi.hoisted(() => ({
	loadState: vi.fn(),
	savePrimaryAccountPropertyScope: vi.fn(),
	saveProfileParameterVisibility: vi.fn(),
	handleError: vi.fn(),
}))

vi.mock('@nextcloud/initial-state', () => ({ loadState: mocks.loadState }))
vi.mock('@nextcloud/event-bus', () => ({ subscribe: vi.fn(), unsubscribe: vi.fn() }))
vi.mock('../../../service/PersonalInfo/PersonalInfoService.js', () => ({
	savePrimaryAccountPropertyScope: mocks.savePrimaryAccountPropertyScope,
}))
vi.mock('../../../service/ProfileService.js', () => ({
	saveProfileParameterVisibility: mocks.saveProfileParameterVisibility,
}))
vi.mock('../../../utils/handlers.ts', () => ({ handleError: mocks.handleError }))
vi.mock('./FederationControl.vue', () => ({
	default: { name: 'FederationControl', render: (h) => h('div', { class: 'federation-control' }) },
}))

const flushPromises = () => new Promise((resolve) => setTimeout(resolve))

const NcSelectStub = { name: 'NcSelect', template: '<div class="nc-select" />' }
const NcPopoverStub = {
	name: 'NcPopover',
	template: '<div class="nc-popover"><slot name="trigger" :attrs="{}" /><slot /></div>',
}

function mountControl(props = {}) {
	return mount(VisibilityScopeControl, {
		propsData: {
			readable: 'Phone number',
			name: 'phone',
			scope: 'v2-local',
			...props,
		},
		stubs: {
			NcButton: true,
			NcIconSvgWrapper: true,
			NcPopover: NcPopoverStub,
			NcSelect: NcSelectStub,
		},
	})
}

/**
 * Emit a selection from the scope dropdown (the second NcSelect in the popover).
 *
 * @param wrapper mounted component wrapper
 * @param scope scope value to select
 */
function selectScope(wrapper, scope: string) {
	const selects = wrapper.findAllComponents({ name: 'NcSelect' })
	return selects.at(1).vm.$emit('option:selected', { name: scope })
}

describe('VisibilityScopeControl', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		mocks.loadState.mockImplementation((app: string, key: string, fallback: unknown) => {
			switch (key) {
				case 'profileParameters':
					return { profileConfig: { phone: { visibility: 'show' } } }
				case 'personalInfoParameters':
					return { profileEnabled: true, profileEnabledGlobally: true }
				case 'accountParameters':
					return { federationEnabled: false, lookupServerUploadEnabled: false }
				default:
					return fallback
			}
		})
	})

	it('shows the combined visibility & scope popover when the profile is enabled', () => {
		const wrapper = mountControl()

		expect(wrapper.find('.nc-popover').exists()).toBe(true)
		expect(wrapper.find('.federation-control').exists()).toBe(false)
	})

	it('falls back to the federation control for additional values', () => {
		const wrapper = mountControl({ additional: true, handleAdditionalScopeChange: vi.fn() })

		expect(wrapper.find('.federation-control').exists()).toBe(true)
		expect(wrapper.find('.nc-popover').exists()).toBe(false)
	})

	it('persists the new scope and emits the change', async () => {
		mocks.savePrimaryAccountPropertyScope.mockResolvedValue({ ocs: { meta: { status: 'ok' } } })
		const wrapper = mountControl()

		await selectScope(wrapper, 'v2-private')
		await flushPromises()

		expect(mocks.savePrimaryAccountPropertyScope).toHaveBeenCalledWith('phone', 'v2-private')
		expect(wrapper.emitted('update:scope')?.[0]).toEqual(['v2-private'])
		expect(mocks.handleError).not.toHaveBeenCalled()
	})

	it('rolls back to the previous scope when saving fails', async () => {
		mocks.savePrimaryAccountPropertyScope.mockRejectedValue(new Error('save failed'))
		const wrapper = mountControl()

		await selectScope(wrapper, 'v2-private')
		await flushPromises()

		const emitted = wrapper.emitted('update:scope')
		expect(emitted?.[0]).toEqual(['v2-private'])
		expect(emitted?.[1]).toEqual(['v2-local'])
		expect(mocks.handleError).toHaveBeenCalled()
	})
})
