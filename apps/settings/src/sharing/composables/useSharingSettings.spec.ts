/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ISharingSettings } from '../types.ts'

import { beforeEach, describe, expect, it, vi } from 'vitest'
import { isReactive, nextTick, watch } from 'vue'

const mocks = vi.hoisted(() => ({
	loadState: vi.fn(),
	setCoreConfigValue: vi.fn(),
	showError: vi.fn(),
}))

vi.mock('@nextcloud/initial-state', () => ({ loadState: mocks.loadState }))
vi.mock('@nextcloud/dialogs', () => ({ showError: mocks.showError }))
vi.mock('../services/appConfig.ts', () => ({ setCoreConfigValue: mocks.setCoreConfigValue }))

import { useSharingSettings } from './useSharingSettings.ts'

describe('useSharingSettings', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		mocks.setCoreConfigValue.mockResolvedValue(undefined)
		mocks.loadState.mockImplementation(() => ({
			enabled: true,
			allowLinks: true,
			excludeGroupsList: [],
			expireAfterNDays: '7',
			defaultPermissions: 31,
			restrictUserEnumerationFullMatchIgnoreSecondDN: false,
		}) as Partial<ISharingSettings>)
	})

	it('loads the settings from the initial state', () => {
		const settings = useSharingSettings()

		expect(mocks.loadState).toHaveBeenCalledWith('settings', 'sharingSettings')
		expect(settings.allowLinks).toBe(true)
		expect(settings.defaultPermissions).toBe(31)
	})

	it('is reactive', async () => {
		const settings = useSharingSettings()
		const onChange = vi.fn()
		watch(() => settings.allowLinks, onChange)

		settings.allowLinks = false
		await nextTick()

		expect(isReactive(settings)).toBe(true)
		expect(onChange).toHaveBeenCalledWith(false, true, expect.anything())
	})

	it.each([
		['allowLinks', false, 'shareapi_allow_links', 'no'],
		['enabled', true, 'shareapi_enabled', 'yes'],
		['restrictUserEnumerationFullMatchIgnoreSecondDN', true, 'shareapi_restrict_user_enumeration_full_match_ignore_second_dn', 'yes'],
		['excludeGroupsList', ['admin', 'group 1'], 'shareapi_exclude_groups_list', '["admin","group 1"]'],
		['expireAfterNDays', '14', 'shareapi_expire_after_n_days', '14'],
		['internalExpireAfterNDays', '3', 'shareapi_internal_expire_after_n_days', '3'],
		['defaultPermissions', 23, 'shareapi_default_permissions', '23'],
	] as const)('stores %s as %s', (setting, value, key, configValue) => {
		const settings = useSharingSettings()

		;(settings as unknown as Record<string, unknown>)[setting] = value

		expect(settings[setting]).toEqual(value)
		expect(mocks.setCoreConfigValue).toHaveBeenCalledOnce()
		expect(mocks.setCoreConfigValue).toHaveBeenCalledWith(key, configValue)
	})

	it('shows an error if a setting cannot be stored', async () => {
		mocks.setCoreConfigValue.mockRejectedValue(new Error('Forbidden'))
		const settings = useSharingSettings()

		settings.allowLinks = false

		await vi.waitFor(() => expect(mocks.showError).toHaveBeenCalledWith('Could not save the sharing setting'))
	})
})
