/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/personal-settings-page.ts'
import { Scope, Visibility } from '../../support/sections/ProfileContactSettingsPage.ts'

test.describe('Settings: Change personal information', () => {
	test.beforeAll(async () => {
		// Prevent the Fediverse section from making outbound HTTP requests
		await runOcc(['config:system:set', 'has_internet_connection', '--type', 'bool', '--value', 'false'])
		// Let each user choose their own language and locale
		await runOcc(['config:system:delete', 'force_language'])
		await runOcc(['config:system:delete', 'force_locale'])
	})

	test.afterAll(async () => {
		await runOcc(['config:system:delete', 'has_internet_connection'])
		// Restore English defaults so other test suites are unaffected
		await runOcc(['config:system:set', 'force_language', '--value', 'en'])
		await runOcc(['config:system:set', 'force_locale', '--value', 'en_US'])
	})

	// ── Profile ───────────────────────────────────────────────────────────────

	test('can enable and disable the profile', async ({ page, profileContactPage, user }) => {
		// Profile is enabled by default: the public profile page shows the user id
		await page.goto(`/u/${user.userId}`)
		await expect(page.getByRole('heading', { name: user.userId })).toBeVisible()

		await profileContactPage.open()
		await expect(profileContactPage.profileSwitch()).toBeChecked()
		await profileContactPage.setProfileEnabled(false)

		// Profile is disabled: the public profile page shows a "not found" heading
		await page.goto(`/u/${user.userId}`, { waitUntil: 'networkidle' })
		await expect(page.getByRole('heading', { name: /Profile not found/i })).toBeVisible()

		// Re-enable the profile
		await profileContactPage.open()
		await expect(profileContactPage.profileSwitch()).not.toBeChecked()
		await profileContactPage.setProfileEnabled(true)

		await page.goto(`/u/${user.userId}`)
		await expect(page.getByRole('heading', { name: user.userId })).toBeVisible()
	})

	test('can open the own profile from the settings', async ({ page, profileContactPage, user }) => {
		await profileContactPage.open()

		// The profile is opened in a new tab
		const profile = page.waitForEvent('popup')
		await page.getByRole('link', { name: 'View full profile' }).click()

		await expect(await profile).toHaveURL(new RegExp(`/u/${user.userId}$`))
	})

	// ── Primary email ─────────────────────────────────────────────────────────

	test('can set primary email and change its scope', async ({ page, profileContactPage, user }) => {
		await profileContactPage.open()

		const emailInput = profileContactPage.primaryEmail()
		// HTML5 email validation: 'foo bar' is not a valid address
		await emailInput.fill('foo bar')
		await expect(emailInput.and(page.locator(':invalid'))).toHaveCount(1)

		// Set a valid email
		await profileContactPage.setEmail(emailInput, 'hello@example.com')

		await profileContactPage.reload()
		await expect(emailInput).toHaveValue('hello@example.com')

		// Change the scope and verify it persists across a reload
		await profileContactPage.setScopeLevel('email', Scope.Local)
		await profileContactPage.reload()
		await expect(profileContactPage.scopeButton('email', Scope.Local)).toBeVisible()

		// With local scope the address is visible on the public profile
		await page.goto(`/u/${user.userId}`)
		await expect(page.getByRole('link', { name: 'hello@example.com' })).toBeVisible()
	})

	test('can delete primary email', async ({ profileContactPage }) => {
		await profileContactPage.open()

		const emailInput = profileContactPage.primaryEmail()
		// Without an address there is nothing to delete
		await expect(profileContactPage.removePrimaryEmailButton()).toBeDisabled()

		await profileContactPage.setEmail(emailInput, 'hello@example.com')
		await profileContactPage.reload()
		await expect(emailInput).toHaveValue('hello@example.com')

		await profileContactPage.deleteEmail()

		await profileContactPage.reload()
		await expect(emailInput).toHaveValue('')
	})

	// ── Additional emails ─────────────────────────────────────────────────────

	test('can set and delete additional emails', async ({ profileContactPage }) => {
		await profileContactPage.open()

		// Adding an address is not possible until a primary email exists
		await expect(profileContactPage.addAdditionalEmailButton()).toBeDisabled()

		// Set a primary email first
		await profileContactPage.setEmail(profileContactPage.primaryEmail(), 'primary@example.com')

		// Add first additional email
		await profileContactPage.addAdditionalEmail()
		// Disabled again until the new field has a value
		await expect(profileContactPage.addAdditionalEmailButton()).toBeDisabled()
		await profileContactPage.setEmail(profileContactPage.additionalEmail(1), '1@example.com')

		// Add second additional email
		await profileContactPage.addAdditionalEmail()
		await profileContactPage.setEmail(profileContactPage.additionalEmail(2), '2@example.com')

		// Both additional addresses persist across a reload
		await profileContactPage.reload()
		await expect(profileContactPage.additionalEmail(1)).toHaveValue('1@example.com')
		await expect(profileContactPage.additionalEmail(2)).toHaveValue('2@example.com')

		// Delete the first additional email via the options of its row
		await profileContactPage.deleteEmail(1)

		// After deletion the second address shifts into position 1
		await profileContactPage.reload()
		await expect(profileContactPage.additionalEmail(1)).toHaveValue('2@example.com')
	})

	// ── Full name ─────────────────────────────────────────────────────────────

	test('can set full name and change its scope', async ({ page, profileContactPage, user }) => {
		await profileContactPage.open()

		await profileContactPage.setProperty('Full name', 'Jane Doe')

		await profileContactPage.reload()
		await expect(profileContactPage.property('Full name')).toHaveValue('Jane Doe')

		await profileContactPage.setScope('Full name', Scope.Local)
		await profileContactPage.reload()
		await profileContactPage.expectScope('Full name', Scope.Local)

		// With local scope the display name appears on the public profile
		await page.goto(`/u/${user.userId}`)
		await expect(page.getByRole('heading', { name: 'Jane Doe' })).toBeVisible()
	})

	// ── Phone number ──────────────────────────────────────────────────────────

	test('can set phone number and its scope', async ({ profileContactPage }) => {
		await profileContactPage.open()

		await profileContactPage.setProperty('Phone number', '+49 89 721010 99701')

		// Server normalises to E.164 format
		await profileContactPage.reload()
		await expect(profileContactPage.property('Phone number')).toHaveValue('+498972101099701')

		await profileContactPage.setScope('Phone number', Scope.Private)
		await profileContactPage.reload()
		await profileContactPage.expectScope('Phone number', Scope.Private)
	})

	test('can set phone number with phone region', async ({ profileContactPage }) => {
		await profileContactPage.open()
		const phoneInput = profileContactPage.property('Phone number')

		// Without a phone region, a local-format number is rejected by the server
		await phoneInput.fill('0 40 428990')

		// Set the default region and reload
		await runOcc(['config:system:set', 'default_phone_region', '--value', 'DE'])
		await profileContactPage.reload()

		await profileContactPage.setProperty('Phone number', '0 40 428990')

		await profileContactPage.reload()
		await expect(phoneInput).toHaveValue('+4940428990')

		await runOcc(['config:system:delete', 'default_phone_region'])
	})

	test('can reset phone number', async ({ profileContactPage }) => {
		await profileContactPage.open()
		const phoneInput = profileContactPage.property('Phone number')

		await profileContactPage.setProperty('Phone number', '+49 40 428990')

		await profileContactPage.reload()
		await expect(phoneInput).toHaveValue('+4940428990')

		await profileContactPage.setProperty('Phone number', '')

		await profileContactPage.reload()
		await expect(phoneInput).toHaveValue('')
	})

	// ── Website ───────────────────────────────────────────────────────────────

	test('can set website and change its scope', async ({ page, profileContactPage, user }) => {
		await profileContactPage.open()

		const websiteInput = profileContactPage.property('Website')
		// HTML5 URL validation: 'foo bar' is not a valid URL
		await websiteInput.fill('foo bar')
		await expect(websiteInput.and(page.locator(':invalid'))).toHaveCount(1)

		await profileContactPage.setProperty('Website', 'http://example.com')

		await profileContactPage.reload()
		await expect(websiteInput).toHaveValue('http://example.com')

		await profileContactPage.setScope('Website', Scope.Private)
		await profileContactPage.reload()
		await profileContactPage.expectScope('Website', Scope.Private)

		// Change to local so the URL appears on the public profile
		await profileContactPage.setScope('Website', Scope.Local)
		await page.goto(`/u/${user.userId}`)
		await expect(page.getByText('http://example.com')).toBeVisible()
	})

	// ── Fediverse ─────────────────────────────────────────────────────────────

	test('can set the Fediverse handle and change its scope', async ({ profileContactPage }) => {
		await profileContactPage.open()

		// Without a handle the Fediverse profile field does not exist yet, so the
		// property only offers a scope
		await expect(profileContactPage.scopeButton('Fediverse (e.g. Mastodon)', Scope.Local)).toBeVisible()

		await profileContactPage.setProperty('Fediverse (e.g. Mastodon)', '@nextcloud@mastodon.social')

		// The server strips the leading '@'
		await profileContactPage.reload()
		await expect(profileContactPage.property('Fediverse (e.g. Mastodon)')).toHaveValue('nextcloud@mastodon.social')

		await profileContactPage.setScope('Fediverse (e.g. Mastodon)', Scope.Private)
		await profileContactPage.reload()
		await profileContactPage.expectScope('Fediverse (e.g. Mastodon)', Scope.Private)
	})

	test('can reset the Fediverse handle', async ({ profileContactPage }) => {
		await profileContactPage.open()
		const fediverseInput = profileContactPage.property('Fediverse (e.g. Mastodon)')

		await profileContactPage.setProperty('Fediverse (e.g. Mastodon)', '@nextcloud@mastodon.social')

		await profileContactPage.reload()
		await expect(fediverseInput).toHaveValue('nextcloud@mastodon.social')

		await profileContactPage.setProperty('Fediverse (e.g. Mastodon)', '')

		await profileContactPage.reload()
		await expect(fediverseInput).toHaveValue('')
	})

	// ── Profile properties (any value, all scopes) ────────────────────────────
	// Each property is tested in its own test so failures are isolated.

	const federatedProperties = [
		{ label: 'Location', value: 'Berlin' },
		{ label: 'Pronouns', value: 'they/them' },
	] as const

	for (const { label, value } of federatedProperties) {
		test(`can set ${label} and change its scope`, async ({ page, profileContactPage, user }) => {
			await profileContactPage.open()

			await profileContactPage.setProperty(label, value)
			await expect(profileContactPage.property(label)).toHaveValue(value)

			// Cycle federated → private and verify the state persists
			await profileContactPage.setScope(label, Scope.Federated)
			await profileContactPage.reload()
			await profileContactPage.expectScope(label, Scope.Federated)

			await profileContactPage.setScope(label, Scope.Private)
			await profileContactPage.reload()
			await profileContactPage.expectScope(label, Scope.Private)

			// With local scope the value appears on the public profile
			await profileContactPage.setScope(label, Scope.Local)
			await page.goto(`/u/${user.userId}`)
			await expect(page.getByText(value)).toBeVisible()
		})
	}

	// ── Non-federated properties (local and private only) ─────────────────────

	const nonfederatedProperties = [
		'Organisation',
		'Role',
		'Headline',
		'About',
	] as const

	for (const label of nonfederatedProperties) {
		test(`can set ${label} and change its scope`, async ({ page, profileContactPage, user }) => {
			// Use a value unique to this property to identify it on the profile page
			const uniqueValue = `${label.toUpperCase()} ${label.toLowerCase()}`
			await profileContactPage.open()

			await profileContactPage.setProperty(label, uniqueValue)

			await profileContactPage.reload()
			await expect(profileContactPage.property(label)).toHaveValue(uniqueValue)

			// Toggle private → local (the two supported scopes for these properties)
			await profileContactPage.setScope(label, Scope.Private)
			await profileContactPage.reload()
			await profileContactPage.expectScope(label, Scope.Private)

			await profileContactPage.setScope(label, Scope.Local)

			// With local scope the value appears on the public profile
			await page.goto(`/u/${user.userId}`)
			await expect(page.getByText(uniqueValue)).toBeVisible()
		})
	}

	// ── Profile visibility ────────────────────────────────────────────────────

	test('can change the profile visibility of a property', async ({ profileContactPage }) => {
		await profileContactPage.open()

		await profileContactPage.setProperty('Headline', 'Hidden headline')
		await profileContactPage.setVisibility('Headline', Visibility.Hidden)

		await profileContactPage.reload()
		await profileContactPage.expectVisibility('Headline', Visibility.Hidden)
	})

	// ── Language & locale ─────────────────────────────────────────────────────

	test.describe('Language & locale', () => {
		test.beforeEach(async () => {
			// The section is read-only while a language or locale is enforced, and a
			// parallel worker may have restored the defaults of this suite already
			await runOcc(['config:system:delete', 'force_language'])
			await runOcc(['config:system:delete', 'force_locale'])
		})

		test('can change language', async ({ page, languageLocalePage }) => {
			await languageLocalePage.open()

			await languageLocalePage.selectLanguage('Nederlands', 'Ned')

			// Language change triggers a full page reload; wait for the Dutch UI
			await expect(page.getByRole('combobox', { name: 'Taal' })).toBeVisible({ timeout: 15_000 })
			await expect(page.getByText('Help met vertalen')).toBeVisible()
		})

		test('can change locale', async ({ page, languageLocalePage }) => {
			await languageLocalePage.open()
			// The example is rendered in the active locale, US English by default
			await expect(languageLocalePage.localeExample()).toContainText(/Example: \d{2}\/\d{2}\/\d{4}/)

			await languageLocalePage.selectLocale('German (Germany)', 'German')

			// Locale change triggers a full page reload
			await page.waitForLoadState('networkidle')
			await languageLocalePage.expectSelected(languageLocalePage.localeSelect(), 'German (Germany)')
			// The example now uses the German date format
			await expect(languageLocalePage.localeExample()).toContainText(/Example: \d{2}\.\d{2}\.\d{4}/)
		})

		test('can change the first day of week', async ({ languageLocalePage }) => {
			await languageLocalePage.open()

			await languageLocalePage.selectFirstDayOfWeek('Monday')

			// The choice persists across a reload
			await languageLocalePage.open()
			await languageLocalePage.expectSelected(languageLocalePage.firstDayOfWeekSelect(), 'Monday')
		})
	})
})
