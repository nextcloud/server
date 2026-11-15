/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ToggleRole } from '../../support/sections/AdminSharingSettingsPage.ts'

import { runOcc } from '@nextcloud/e2e-test-server'
import { expect, test } from '../../support/fixtures/admin-sharing-settings-page.ts'
import { getToast } from '../../support/utils/toast.ts'

interface ToggleSetting {
	role: ToggleRole
	name: string
	/** The app config key of core the setting is stored in */
	key: string
	/** Whether the setting is enabled on a fresh instance */
	enabled: boolean
	/** Settings that have to be enabled for this one to be shown */
	requires?: string[]
}

const TOGGLES: ToggleSetting[] = [
	{ role: 'switch', name: 'Allow apps to use the Share API', key: 'shareapi_enabled', enabled: true },
	{ role: 'checkbox', name: 'Allow resharing', key: 'shareapi_allow_resharing', enabled: true },
	{ role: 'checkbox', name: 'Allow sharing with groups', key: 'shareapi_allow_group_sharing', enabled: true },
	{ role: 'checkbox', name: 'Restrict users to only share with users in their groups', key: 'shareapi_only_share_with_group_members', enabled: false },
	{ role: 'checkbox', name: 'Allow users to preview files even if download is disabled', key: 'shareapi_allow_view_without_download', enabled: true },
	{ role: 'switch', name: 'Allow users to share via link and emails', key: 'shareapi_allow_links', enabled: true },
	{ role: 'checkbox', name: 'Allow public uploads', key: 'shareapi_allow_public_upload', enabled: true },
	{
		role: 'checkbox',
		name: 'Allow public shares to be added to other clouds by federation. This will add share permissions to all newly created link shares.',
		key: 'shareapi_allow_federation_on_public_shares',
		enabled: true,
	},
	{ role: 'checkbox', name: 'Always ask for a password', key: 'shareapi_enable_link_password_by_default', enabled: false },
	{ role: 'checkbox', name: 'Enforce password protection', key: 'shareapi_enforce_links_password', enabled: false, requires: ['shareapi_enable_link_password_by_default'] },
	{ role: 'switch', name: 'Allow users to set custom share link tokens', key: 'shareapi_allow_custom_tokens', enabled: false },
	{ role: 'switch', name: 'Set default expiration date for internal shares', key: 'shareapi_default_internal_expire_date', enabled: false },
	{ role: 'checkbox', name: 'Enforce expiration date', key: 'shareapi_enforce_internal_expire_date', enabled: false, requires: ['shareapi_default_internal_expire_date'] },
	{ role: 'switch', name: 'Set default expiration date for shares to other servers', key: 'shareapi_default_remote_expire_date', enabled: false },
	{ role: 'checkbox', name: 'Enforce expiration date for remote shares', key: 'shareapi_enforce_remote_expire_date', enabled: false, requires: ['shareapi_default_remote_expire_date'] },
	{ role: 'switch', name: 'Set default expiration date for shares via link or mail', key: 'shareapi_default_expire_date', enabled: false },
	{ role: 'checkbox', name: 'Enforce expiration date for link or mail shares', key: 'shareapi_enforce_expire_date', enabled: false, requires: ['shareapi_default_expire_date'] },
	{
		role: 'switch',
		name: 'Allow account name autocompletion in share dialog and allow access to the system address book',
		key: 'shareapi_allow_share_dialog_user_enumeration',
		enabled: true,
	},
	{
		role: 'checkbox',
		name: 'Restrict account name autocompletion and system address book access to users within the same groups',
		key: 'shareapi_restrict_user_enumeration_to_group',
		enabled: false,
	},
	{ role: 'checkbox', name: 'Restrict account name autocompletion to users based on their phonebook', key: 'shareapi_restrict_user_enumeration_to_phone', enabled: false },
	{
		role: 'switch',
		name: 'Allow autocompletion to full match when entering the full name (ignoring restrictions like group membership or missing phonebook match)',
		key: 'shareapi_restrict_user_enumeration_full_match',
		enabled: true,
	},
	{ role: 'checkbox', name: 'Also allow autocompletion on full match of the user ID', key: 'shareapi_restrict_user_enumeration_full_match_user_id', enabled: true },
	{ role: 'checkbox', name: 'Also allow autocompletion on full match of the display name', key: 'shareapi_restrict_user_enumeration_full_match_displayname', enabled: true },
	{ role: 'checkbox', name: 'Also allow autocompletion on full match of the user email', key: 'shareapi_restrict_user_enumeration_full_match_email', enabled: true },
	{ role: 'checkbox', name: 'Do not use second user displayname for full match', key: 'shareapi_restrict_user_enumeration_full_match_ignore_second_dn', enabled: false },
]

/** Every config key of core the tests change, reset to its default after each test. */
const CONFIG_KEYS = [
	...TOGGLES.map(({ key }) => key),
	'shareapi_only_share_with_group_members_exclude_group_list',
	'shareapi_allow_links_exclude_groups',
	'shareapi_enforce_links_password_excluded_groups',
	'shareapi_exclude_groups',
	'shareapi_exclude_groups_list',
	'shareapi_internal_expire_after_n_days',
	'shareapi_remote_expire_after_n_days',
	'shareapi_expire_after_n_days',
	'shareapi_public_link_disclaimertext',
	'shareapi_default_permissions',
]

/**
 * Reset the sharing settings to their defaults, only touching the keys that
 * are set as every occ call is costly.
 */
async function resetSharingSettings(): Promise<void> {
	const { stdout } = await runOcc(['config:list', '--output=json', 'core'])
	const config: Record<string, unknown> = JSON.parse(stdout).apps?.core ?? {}
	for (const key of CONFIG_KEYS.filter((key) => key in config)) {
		await runOcc(['config:app:delete', 'core', key])
	}
}

test.describe('Settings: Admin sharing settings', () => {
	test.beforeAll(resetSharingSettings)
	test.afterEach(resetSharingSettings)

	test('shows the section with its documentation', async ({ sharingSettings }) => {
		await sharingSettings.open()

		await expect(sharingSettings.heading()).toBeVisible()
		await expect(sharingSettings.documentationLink()).toHaveAttribute('href', /admin-sharing/)
		await expect(sharingSettings.sharingAppDisabledNote()).toHaveCount(0)
	})

	test('shows the current configuration', async ({ sharingSettings }) => {
		// Enabled by default and hiding other settings when disabled
		const VISIBILITY_PARENTS = [
			'shareapi_enabled',
			'shareapi_allow_links',
			'shareapi_allow_share_dialog_user_enumeration',
			'shareapi_restrict_user_enumeration_full_match',
		]

		for (const { key, enabled } of TOGGLES) {
			await runOcc(['config:app:set', '--value', enabled ? 'no' : 'yes', 'core', key])
		}
		// Keep the parents of every nested setting visible
		for (const key of VISIBILITY_PARENTS) {
			await runOcc(['config:app:set', '--value', 'yes', 'core', key])
		}
		await runOcc(['config:app:set', '--value', '11', 'core', 'shareapi_internal_expire_after_n_days'])
		await runOcc(['config:app:set', '--value', String(1 | 4 | 16), 'core', 'shareapi_default_permissions'])

		await sharingSettings.open()

		for (const { role, name, key, enabled } of TOGGLES) {
			await expect(sharingSettings.toggle(role, name), name).toBeChecked({ checked: VISIBILITY_PARENTS.includes(key) || !enabled })
		}
		await expect(sharingSettings.numberInput('Default expiration time of new shares in days')).toHaveValue('11')
		await expect(sharingSettings.defaultPermission('Create')).toBeChecked()
		await expect(sharingSettings.defaultPermission('Change')).not.toBeChecked()
		await expect(sharingSettings.defaultPermission('Delete')).not.toBeChecked()
		await expect(sharingSettings.defaultPermission('Reshare')).toBeChecked()
	})

	for (const { role, name, key, enabled, requires } of TOGGLES) {
		test(`stores "${name}"`, async ({ page, sharingSettings }) => {
			for (const parent of requires ?? []) {
				await runOcc(['config:app:set', '--value', 'yes', 'core', parent])
			}
			await sharingSettings.open()

			const toggle = sharingSettings.toggle(role, name)
			await expect(toggle).toBeChecked({ checked: enabled })

			const saved = sharingSettings.waitForConfigUpdate(key, enabled ? 'no' : 'yes')
			await sharingSettings.setChecked(toggle, !enabled)
			expect((await saved).ok()).toBe(true)

			await page.reload()
			await expect(sharingSettings.toggle(role, name)).toBeChecked({ checked: !enabled })
		})
	}

	test('hides all sharing settings but the default permissions when the Share API is disabled', async ({ page, sharingSettings }) => {
		await sharingSettings.open()

		const saved = sharingSettings.waitForConfigUpdate('shareapi_enabled', 'no')
		await sharingSettings.setChecked(sharingSettings.shareApiSwitch(), false)
		await saved

		await expect(sharingSettings.toggle('checkbox', 'Allow resharing')).toBeHidden()
		await expect(sharingSettings.toggle('switch', 'Allow users to share via link and emails')).toBeHidden()
		await expect(page.getByRole('heading', { name: 'Privacy settings for sharing' })).toBeHidden()
		await expect(page.getByRole('heading', { name: 'Default share permissions' })).toBeVisible()
		await expect(sharingSettings.defaultPermission('Create')).toBeVisible()

		await sharingSettings.setChecked(sharingSettings.shareApiSwitch(), true)
		await expect(sharingSettings.toggle('checkbox', 'Allow resharing')).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Privacy settings for sharing' })).toBeVisible()
	})

	test('shows the link share settings only while link sharing is allowed', async ({ sharingSettings }) => {
		await sharingSettings.open()
		const linkExpiration = sharingSettings.toggle('switch', 'Set default expiration date for shares via link or mail')

		await expect(sharingSettings.toggle('checkbox', 'Allow public uploads')).toBeVisible()
		await expect(sharingSettings.groupSelect('Exclude groups from creating link shares')).toBeVisible()
		await expect(linkExpiration).toBeEnabled()

		const saved = sharingSettings.waitForConfigUpdate('shareapi_allow_links', 'no')
		await sharingSettings.setChecked(sharingSettings.toggle('switch', 'Allow users to share via link and emails'), false)
		await saved

		await expect(sharingSettings.toggle('checkbox', 'Allow public uploads')).toBeHidden()
		await expect(sharingSettings.groupSelect('Exclude groups from creating link shares')).toBeHidden()
		await expect(linkExpiration).toBeDisabled()
	})

	test('only offers to enforce link passwords when asking for them', async ({ sharingSettings }) => {
		await sharingSettings.open()
		const enforce = sharingSettings.toggle('checkbox', 'Enforce password protection')
		await expect(enforce).toBeHidden()

		await sharingSettings.setChecked(sharingSettings.toggle('checkbox', 'Always ask for a password'), true)
		await expect(enforce).toBeVisible()
	})

	test('warns that previews do not protect against screenshots', async ({ page, sharingSettings }) => {
		await sharingSettings.open()
		const warning = page.getByText('Users will still be able to screenshot or record the screen.')
		await expect(warning).toBeVisible()

		await sharingSettings.setChecked(sharingSettings.toggle('checkbox', 'Allow users to preview files even if download is disabled'), false)
		await expect(warning).toBeHidden()
	})

	test.describe('expiration dates', () => {
		const EXPIRATIONS = [
			{ toggle: 'Set default expiration date for internal shares', input: 'Default expiration time of new shares in days', key: 'shareapi_internal_expire_after_n_days' },
			{ toggle: 'Set default expiration date for shares to other servers', input: 'Default expiration time of remote shares in days', key: 'shareapi_remote_expire_after_n_days' },
			{ toggle: 'Set default expiration date for shares via link or mail', input: 'Default expiration time of shares in days', key: 'shareapi_expire_after_n_days' },
		]

		for (const { toggle, input, key } of EXPIRATIONS) {
			test(`stores the days of "${toggle}"`, async ({ page, sharingSettings }) => {
				await sharingSettings.open()
				const days = sharingSettings.numberInput(input)
				await expect(days).toBeHidden()

				await sharingSettings.setChecked(sharingSettings.toggle('switch', toggle), true)
				await expect(days).toHaveValue('7')

				const saved = sharingSettings.waitForConfigUpdate(key, '14')
				await days.fill('14')
				expect((await saved).ok()).toBe(true)

				await page.reload()
				await expect(sharingSettings.numberInput(input)).toHaveValue('14')
			})
		}
	})

	test.describe('group selection', () => {
		const group = `sharing-settings-${crypto.randomUUID().slice(0, 8)}`

		test.beforeAll(async () => {
			await runOcc(['group:add', group])
		})

		test.afterAll(async () => {
			await runOcc(['group:delete', group])
			await runOcc(['config:system:delete', 'sharing.allow_disabled_password_enforcement_groups'])
		})

		test('excludes groups from creating link shares', async ({ page, sharingSettings }) => {
			await sharingSettings.open()

			const saved = sharingSettings.waitForConfigUpdate('shareapi_allow_links_exclude_groups', JSON.stringify([group]))
			await sharingSettings.addGroup('Exclude groups from creating link shares', group)
			expect((await saved).ok()).toBe(true)

			await page.reload()
			await expect(sharingSettings.selectedGroup(group)).toBeVisible()
		})

		test('ignores groups when restricting sharing to group members', async ({ page, sharingSettings }) => {
			const label = 'Ignore the following groups when checking group membership'
			await sharingSettings.open()
			await expect(sharingSettings.groupSelect(label)).toBeHidden()

			await sharingSettings.setChecked(sharingSettings.toggle('checkbox', 'Restrict users to only share with users in their groups'), true)
			const saved = sharingSettings.waitForConfigUpdate('shareapi_only_share_with_group_members_exclude_group_list', JSON.stringify([group]))
			await sharingSettings.addGroup(label, group)
			expect((await saved).ok()).toBe(true)

			await page.reload()
			await expect(sharingSettings.selectedGroup(group)).toBeVisible()
		})

		test('excludes groups from the link password requirement if enabled by the system config', async ({ page, sharingSettings }) => {
			const label = 'Exclude groups from password requirements'
			await sharingSettings.open()
			await expect(sharingSettings.groupSelect(label)).toHaveCount(0)

			await runOcc(['config:system:set', '--type', 'boolean', '--value', 'true', 'sharing.allow_disabled_password_enforcement_groups'])
			await runOcc(['config:app:set', '--value', 'yes', 'core', 'shareapi_enable_link_password_by_default'])
			await page.reload()

			// Only applicable while passwords are enforced
			await expect(sharingSettings.groupSelect(label)).toBeDisabled()
			await sharingSettings.setChecked(sharingSettings.toggle('checkbox', 'Enforce password protection'), true)
			await expect(sharingSettings.groupSelect(label)).toBeEnabled()

			const saved = sharingSettings.waitForConfigUpdate('shareapi_enforce_links_password_excluded_groups', JSON.stringify([group]))
			await sharingSettings.addGroup(label, group)
			expect((await saved).ok()).toBe(true)

			await page.reload()
			await expect(sharingSettings.selectedGroup(group)).toBeVisible()
		})

		test('limits sharing to an allow list or block list of groups', async ({ page, sharingSettings }) => {
			const everyone = sharingSettings.toggle('radio', 'Allow sharing for everyone (default)')
			const blockList = sharingSettings.toggle('radio', 'Exclude some groups (block list)')
			const allowList = sharingSettings.toggle('radio', 'Allow some groups only (allow list)')

			await sharingSettings.open()
			await expect(everyone).toBeChecked()
			await expect(sharingSettings.groupSelect('Groups excluded from sharing')).toBeHidden()

			let saved = sharingSettings.waitForConfigUpdate('shareapi_exclude_groups', 'yes')
			await sharingSettings.setChecked(blockList, true)
			expect((await saved).ok()).toBe(true)
			await expect(blockList).toBeChecked()
			await expect(page.getByText('A member of at least one of these groups will not be able to initiate new shares')).toBeVisible()

			saved = sharingSettings.waitForConfigUpdate('shareapi_exclude_groups_list', JSON.stringify([group]))
			await sharingSettings.addGroup('Groups excluded from sharing', group)
			expect((await saved).ok()).toBe(true)

			saved = sharingSettings.waitForConfigUpdate('shareapi_exclude_groups', 'allow')
			await sharingSettings.setChecked(allowList, true)
			expect((await saved).ok()).toBe(true)
			await expect(sharingSettings.groupSelect('Groups allowed to share')).toBeVisible()
			await expect(page.getByText('Only members of at least one of these groups will be able to initiate shares.')).toBeVisible()

			await page.reload()
			await expect(allowList).toBeChecked()
			await expect(sharingSettings.selectedGroup(group)).toBeVisible()

			saved = sharingSettings.waitForConfigUpdate('shareapi_exclude_groups', 'no')
			await sharingSettings.setChecked(everyone, true)
			expect((await saved).ok()).toBe(true)
			await expect(sharingSettings.groupSelect('Groups allowed to share')).toBeHidden()
		})
	})

	test('stores the public share disclaimer', async ({ page, sharingSettings }) => {
		const toggle = sharingSettings.toggle('switch', 'Show disclaimer text on the public link upload page (only shown when the file list is hidden)')
		await sharingSettings.open()
		await expect(sharingSettings.disclaimerInput()).toHaveCount(0)

		await sharingSettings.setChecked(toggle, true)
		const saved = sharingSettings.waitForConfigUpdate('shareapi_public_link_disclaimertext', 'Please do not upload secrets')
		await sharingSettings.disclaimerInput().fill('Please do not upload secrets')
		expect((await saved).ok()).toBe(true)
		await expect(getToast(page, 'Changed disclaimer text')).toBeVisible()

		await page.reload()
		await expect(toggle).toBeChecked()
		await expect(sharingSettings.disclaimerInput()).toHaveValue('Please do not upload secrets')

		const deleted = sharingSettings.waitForConfigDeletion('shareapi_public_link_disclaimertext')
		await sharingSettings.setChecked(toggle, false)
		expect((await deleted).ok()).toBe(true)
		await expect(getToast(page, 'Deleted disclaimer text')).toBeVisible()
		await expect(sharingSettings.disclaimerInput()).toHaveCount(0)

		await page.reload()
		await expect(toggle).not.toBeChecked()
	})

	test('stores the default share permissions', async ({ page, sharingSettings }) => {
		await sharingSettings.open()
		for (const permission of ['Create', 'Change', 'Delete', 'Reshare'] as const) {
			await expect(sharingSettings.defaultPermission(permission)).toBeChecked()
		}

		// All permissions (31) without delete (8)
		let saved = sharingSettings.waitForConfigUpdate('shareapi_default_permissions', '23')
		await sharingSettings.setChecked(sharingSettings.defaultPermission('Delete'), false)
		expect((await saved).ok()).toBe(true)

		// … and without reshare (16)
		saved = sharingSettings.waitForConfigUpdate('shareapi_default_permissions', '7')
		await sharingSettings.setChecked(sharingSettings.defaultPermission('Reshare'), false)
		expect((await saved).ok()).toBe(true)

		await page.reload()
		await expect(sharingSettings.defaultPermission('Create')).toBeChecked()
		await expect(sharingSettings.defaultPermission('Change')).toBeChecked()
		await expect(sharingSettings.defaultPermission('Delete')).not.toBeChecked()
		await expect(sharingSettings.defaultPermission('Reshare')).not.toBeChecked()
	})

	test.describe('without the sharing app', () => {
		test.afterEach(async () => {
			await runOcc(['app:enable', 'files_sharing'])
		})

		test('asks to enable the sharing app', async ({ sharingSettings }) => {
			await runOcc(['app:disable', 'files_sharing'])
			await sharingSettings.open()

			await expect(sharingSettings.sharingAppDisabledNote()).toBeVisible()
			await expect(sharingSettings.shareApiSwitch()).toHaveCount(0)
		})
	})
})
