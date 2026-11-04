/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ISharingSettings } from '../types.ts'

import { cleanup, fireEvent, render } from '@testing-library/vue'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

const mocks = vi.hoisted(() => ({
	settings: {} as Partial<ISharingSettings>,
	setCoreConfigValue: vi.fn(),
	deleteCoreConfigValue: vi.fn(),
	showError: vi.fn(),
	showSuccess: vi.fn(),
}))

vi.mock('@nextcloud/axios')
vi.mock('@nextcloud/initial-state', () => ({ loadState: () => structuredClone(mocks.settings) }))
vi.mock('@nextcloud/dialogs', () => ({ showError: mocks.showError, showSuccess: mocks.showSuccess }))
vi.mock('../services/appConfig.ts', () => ({
	setCoreConfigValue: mocks.setCoreConfigValue,
	deleteCoreConfigValue: mocks.deleteCoreConfigValue,
}))

import AdminSettingsSharingForm from './AdminSettingsSharingForm.vue'

describe('AdminSettingsSharingForm', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		mocks.setCoreConfigValue.mockResolvedValue(undefined)
		mocks.deleteCoreConfigValue.mockResolvedValue(undefined)
		mocks.settings = {
			enabled: true,
			allowLinks: true,
			allowLinksExcludeGroups: [],
			enforceLinksPasswordExcludedGroups: [],
			onlyShareWithGroupMembersExcludeGroupList: [],
			excludeGroups: 'no',
			excludeGroupsList: [],
			publicShareDisclaimerText: '',
			defaultPermissions: 31,
			expireAfterNDays: '7',
			internalExpireAfterNDays: '7',
			remoteExpireAfterNDays: '7',
		}
	})

	afterEach(() => {
		cleanup()
		vi.useRealTimers()
	})

	it('hides the sharing settings while the Share API is disabled', () => {
		mocks.settings.enabled = false
		const { getByRole } = render(AdminSettingsSharingForm)

		expect(getByRole('switch', { name: 'Allow apps to use the Share API' })).not.toBeChecked()
		expect(getByRole('checkbox', { name: 'Allow resharing', hidden: true })).not.toBeVisible()
		expect(getByRole('checkbox', { name: 'Create' })).toBeVisible()
	})

	it('stores a changed setting', async () => {
		const { getByRole } = render(AdminSettingsSharingForm)

		await fireEvent.click(getByRole('switch', { name: 'Allow users to share via link and emails' }))

		expect(mocks.setCoreConfigValue).toHaveBeenCalledOnce()
		expect(mocks.setCoreConfigValue).toHaveBeenCalledWith('shareapi_allow_links', 'no')
	})

	it('stores the group sharing limitation', async () => {
		const { getByRole } = render(AdminSettingsSharingForm)

		await fireEvent.click(getByRole('radio', { name: 'Allow some groups only (allow list)' }))

		expect(getByRole('radio', { name: 'Allow some groups only (allow list)' })).toBeChecked()
		expect(mocks.setCoreConfigValue).toHaveBeenCalledOnce()
		expect(mocks.setCoreConfigValue).toHaveBeenCalledWith('shareapi_exclude_groups', 'allow')
	})

	it('stores the default permissions', async () => {
		const { getByRole } = render(AdminSettingsSharingForm)

		await fireEvent.click(getByRole('checkbox', { name: 'Reshare' }))

		expect(mocks.setCoreConfigValue).toHaveBeenCalledWith('shareapi_default_permissions', '15')
	})

	it('stores the disclaimer text once typing stopped', async () => {
		vi.useFakeTimers()
		const { getByRole, findByRole } = render(AdminSettingsSharingForm)

		await fireEvent.click(getByRole('switch', { name: /Show disclaimer text/ }))
		const textarea = await findByRole('textbox', { name: 'Disclaimer text' })
		await fireEvent.update(textarea, 'Do not')
		await fireEvent.update(textarea, 'Do not upload')
		expect(mocks.setCoreConfigValue).not.toHaveBeenCalled()

		await vi.advanceTimersByTimeAsync(500)
		expect(mocks.setCoreConfigValue).toHaveBeenCalledOnce()
		expect(mocks.setCoreConfigValue).toHaveBeenCalledWith('shareapi_public_link_disclaimertext', 'Do not upload')
		expect(mocks.showSuccess).toHaveBeenCalledWith('Changed disclaimer text')
	})

	it('removes the disclaimer text when disabled', async () => {
		vi.useFakeTimers()
		mocks.settings.publicShareDisclaimerText = 'Do not upload'
		const { getByRole, queryByRole } = render(AdminSettingsSharingForm)
		expect(getByRole('textbox', { name: 'Disclaimer text' })).toHaveValue('Do not upload')

		await fireEvent.click(getByRole('switch', { name: /Show disclaimer text/ }))
		expect(queryByRole('textbox', { name: 'Disclaimer text' })).toBeNull()

		await vi.advanceTimersByTimeAsync(500)
		expect(mocks.deleteCoreConfigValue).toHaveBeenCalledWith('shareapi_public_link_disclaimertext')
		expect(mocks.setCoreConfigValue).not.toHaveBeenCalled()
		expect(mocks.showSuccess).toHaveBeenCalledWith('Deleted disclaimer text')
	})

	it('shows an error if the disclaimer text cannot be stored', async () => {
		vi.useFakeTimers()
		mocks.setCoreConfigValue.mockRejectedValue(new Error('Forbidden'))
		const { getByRole, findByRole } = render(AdminSettingsSharingForm)

		await fireEvent.click(getByRole('switch', { name: /Show disclaimer text/ }))
		await fireEvent.update(await findByRole('textbox', { name: 'Disclaimer text' }), 'Do not upload')
		await vi.advanceTimersByTimeAsync(500)

		expect(mocks.showError).toHaveBeenCalledWith('Could not set disclaimer text')
		expect(mocks.showSuccess).not.toHaveBeenCalled()
	})
})
