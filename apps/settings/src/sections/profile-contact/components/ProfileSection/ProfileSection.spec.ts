/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { h } from 'vue'
import ProfileSection from './ProfileSection.vue'

// The settings constants reference these l10n helpers as build-provided globals.
vi.hoisted(() => {
	globalThis.t = (app: string, text: string) => text
	globalThis.n = (app: string, singular: string, plural: string, count: number) => (count === 1 ? singular : plural)
})

const mocks = vi.hoisted(() => ({
	savePrimaryAccountProperty: vi.fn(),
	handleError: vi.fn(),
	emit: vi.fn(),
}))

vi.mock('@nextcloud/event-bus', () => ({ emit: mocks.emit, subscribe: vi.fn(), unsubscribe: vi.fn() }))
vi.mock('@nextcloud/initial-state', () => ({
	loadState: () => ({ statusMessage: '', displayName: { value: 'Alice' }, profileEnabled: true, userId: 'alice' }),
}))
vi.mock('../../../../service/PersonalInfo/PersonalInfoService.js', () => ({
	savePrimaryAccountProperty: mocks.savePrimaryAccountProperty,
}))
vi.mock('../../../../utils/handlers.ts', () => ({ handleError: mocks.handleError }))
vi.mock('./ProfilePreviewCard.vue', () => ({ default: { render: () => h('div') } }))

const flushPromises = () => new Promise((resolve) => setTimeout(resolve))

/**
 * Mount the section and turn the profile off
 */
async function disableProfile() {
	const wrapper = mount(ProfileSection)
	await wrapper.find('input[type="checkbox"]').setValue(false)
	await flushPromises()
	return wrapper
}

describe('ProfileSection', () => {
	beforeEach(() => {
		mocks.savePrimaryAccountProperty.mockReset()
		mocks.handleError.mockReset()
		mocks.emit.mockReset()
	})

	it('disables the profile', async () => {
		mocks.savePrimaryAccountProperty.mockResolvedValue({ ocs: { meta: { status: 'ok' } } })
		const wrapper = await disableProfile()

		expect(mocks.savePrimaryAccountProperty).toHaveBeenCalledWith('profile_enabled', false)
		expect(mocks.emit).toHaveBeenCalledWith('settings:profile-enabled:updated', false)
		expect(wrapper.find('input[type="checkbox"]').element.checked).toBe(false)
	})

	it('resets the switch when saving fails', async () => {
		mocks.savePrimaryAccountProperty.mockRejectedValue(new Error('500'))
		const wrapper = await disableProfile()

		expect(mocks.handleError).toHaveBeenCalled()
		expect(mocks.emit).not.toHaveBeenCalled()
		expect(wrapper.find('input[type="checkbox"]').element.checked).toBe(true)
	})
})
