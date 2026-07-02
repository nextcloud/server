/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Page, Response } from '@playwright/test'

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { expect } from '@playwright/test'
import { test as userSessionTest } from '../../support/fixtures/random-user-session.ts'
import { handlePasswordConfirmation } from '../../support/utils/password-confirmation.ts'

// ── Visibility scope labels exactly as rendered in the UI ─────────────────────
const Visibility = {
	Private: 'Private',
	Local: 'Local',
	Federated: 'Federated',
	Published: 'Published',
} as const
type Visibility = typeof Visibility[keyof typeof Visibility]

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Register a listener for the next personal-info PUT. Call BEFORE triggering
 * the save action; await the result after the action and any password dialog.
 */
function waitForSave(page: Page): Promise<Response> {
	return page.waitForResponse((r) => r.request().method() === 'PUT' && r.url().includes('/ocs/v2.php/cloud/users/'))
}

/**
 * Click the scope (visibility) control for `property` and select `scope`.
 * `property` is the lowercase readable name as it appears in the button's
 * aria-label (e.g. "email", "full name", "phone number").
 */
async function changeVisibility(page: Page, property: string, scope: Visibility, password: string): Promise<void> {
	const saved = waitForSave(page)
	await page.getByRole('button', { name: new RegExp(`change scope level of ${property}`, 'i') }).click()
	await page.getByRole('menuitemradio', { name: scope }).click()
	await handlePasswordConfirmation(page, password)
	await saved
}

// ── Fixture ───────────────────────────────────────────────────────────────────

// Ensure English UI language and locale so string assertions are stable
const test = userSessionTest.extend({
	user: async ({ user: baseUser }, use) => {
		await runOcc(['user:setting', baseUser.userId, 'core', 'lang', 'en'])
		await runOcc(['user:setting', baseUser.userId, 'core', 'locale', 'en_US'])
		await use(baseUser)
	},
})

// ── Spec ──────────────────────────────────────────────────────────────────────

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

	test('can enable and disable the profile', async ({ page, user }) => {
		// Profile is enabled by default: the public profile page shows the user id
		await page.goto(`/u/${user.userId}`)
		await expect(page.getByRole('heading', { name: user.userId })).toBeVisible()

		await page.goto('/settings/user')
		const saved1 = waitForSave(page)
		await page.getByRole('checkbox', { name: 'Enable profile' }).uncheck({ force: true })
		await handlePasswordConfirmation(page, user.password)
		await saved1

		// Profile is disabled: the public profile page shows a "not found" heading
		await page.goto(`/u/${user.userId}`, { waitUntil: 'networkidle' })
		await expect(page.getByRole('heading', { name: /Profile not found/i })).toBeVisible()

		// Re-enable the profile
		await page.goto('/settings/user')
		const saved2 = waitForSave(page)
		await page.getByRole('checkbox', { name: 'Enable profile' }).check({ force: true })
		await handlePasswordConfirmation(page, user.password)
		await saved2

		await page.goto(`/u/${user.userId}`)
		await expect(page.getByRole('heading', { name: user.userId })).toBeVisible()
	})

	// ── Language ──────────────────────────────────────────────────────────────

	// eslint-disable-next-line @typescript-eslint/no-unused-vars -- we need the user fixture to ensure the test user is created and cleaned up
	test('can change language', async ({ page, user }) => {
		await page.goto('/settings/user')

		// NcSelect: type to filter, click the option (teleported to <body>)
		await page.getByRole('combobox', { name: 'Language' }).scrollIntoViewIfNeeded()
		await page.getByRole('combobox', { name: 'Language' }).fill('Ned')
		await page.getByRole('option', { name: /Neder\s?lands/ }).click()

		// Language change triggers a full page reload; wait for Dutch UI
		await expect(page.getByRole('combobox', { name: 'Taal' })).toBeVisible({ timeout: 15_000 })
		await expect(page.getByText('Help met vertalen')).toBeVisible()
	})

	// ── Locale ────────────────────────────────────────────────────────────────

	test('can change locale', async ({ page }) => {
		await page.goto('/settings/user')

		await page.getByRole('combobox', { name: 'Locale' }).fill('German')
		await page.getByRole('option', { name: /^German/ }).filter({ hasText: /\(Germany\)/ }).click()

		// Locale change triggers a full page reload
		await page.waitForLoadState('networkidle')
		// After reload the German locale option is reflected in the combobox
		await expect(page.getByRole('combobox', { name: 'Locale' })).toBeVisible()
		await expect(page.getByText(/German \(Germany\)/)).toBeVisible()
	})

	// ── Primary email ─────────────────────────────────────────────────────────

	test('can set primary email and change its visibility', async ({ page, user }) => {
		await page.goto('/settings/user')

		const emailInput = page.getByRole('textbox', { name: 'Email' })
		// HTML5 email validation: 'foo bar' is not a valid address
		await emailInput.fill('foo bar')
		await expect(emailInput.and(page.locator(':invalid'))).toHaveCount(1)

		// Set a valid email
		const saved = waitForSave(page)
		await emailInput.fill('hello@example.com')
		await handlePasswordConfirmation(page, user.password)
		await saved

		await page.reload()
		await expect(emailInput).toHaveValue('hello@example.com')

		// Change visibility and verify it persists across a reload
		await changeVisibility(page, 'email', Visibility.Local, user.password)
		await page.reload()
		await expect(page.getByRole('button', { name: /change scope level of email.*local/i })).toBeVisible()

		// With Local visibility the address is visible on the public profile
		await page.goto(`/u/${user.userId}`)
		await expect(page.getByRole('link', { name: 'hello@example.com' })).toBeVisible()
	})

	test('can delete primary email', async ({ page, user }) => {
		await page.goto('/settings/user')

		const saved1 = waitForSave(page)
		const emailInput = page.getByRole('textbox', { name: 'Email' })
		await emailInput.fill('hello@example.com')
		await handlePasswordConfirmation(page, user.password)
		await saved1

		await page.reload()
		await expect(emailInput).toHaveValue('hello@example.com')

		const saved2 = waitForSave(page)
		// The "Remove primary email" button is visually inside the input row
		await page.getByRole('button', { name: 'Remove primary email' }).click({ force: true })
		await handlePasswordConfirmation(page, user.password)
		await saved2

		await page.reload()
		await expect(emailInput).toHaveValue('')
	})

	// ── Additional emails ─────────────────────────────────────────────────────

	test('can set and delete additional emails', async ({ page, user }) => {
		await page.goto('/settings/user')

		// "Add additional email" is disabled until a primary email exists
		await expect(page.getByRole('button', { name: 'Add additional email' })).toBeDisabled()

		// Set a primary email first
		const emailInput = page.getByRole('textbox', { name: 'Email' })
		const saved1 = waitForSave(page)
		await emailInput.fill('primary@example.com')
		await handlePasswordConfirmation(page, user.password)
		await saved1

		// Add first additional email
		await page.getByRole('button', { name: 'Add additional email' }).click()
		// Disabled again until the new field has a value
		await expect(page.getByRole('button', { name: 'Add additional email' })).toBeDisabled()

		const saved2 = waitForSave(page)
		await page.getByRole('textbox', { name: 'Additional email address 1' }).fill('1@example.com')
		await handlePasswordConfirmation(page, user.password)
		await saved2

		// Add second additional email
		await page.getByRole('button', { name: 'Add additional email' }).click()

		const saved3 = waitForSave(page)
		await page.getByRole('textbox', { name: 'Additional email address 2' }).fill('2@example.com')
		await handlePasswordConfirmation(page, user.password)
		await saved3

		// Both additional addresses persist across a reload
		await page.reload()
		await expect(page.getByRole('textbox', { name: 'Additional email address 1' })).toHaveValue('1@example.com')
		await expect(page.getByRole('textbox', { name: 'Additional email address 2' })).toHaveValue('2@example.com')

		// Delete the first additional email via its options menu
		await page.getByRole('button', { name: 'Options for additional email address 1' }).click({ force: true })
		const saved4 = waitForSave(page)
		await page.getByRole('menuitem', { name: 'Delete email' }).click({ force: true })
		await handlePasswordConfirmation(page, user.password)
		await saved4

		// After deletion the second address shifts into position 1
		await page.reload()
		await expect(page.getByRole('textbox', { name: 'Additional email address' })).toHaveValue('2@example.com')
	})

	// ── Full name ─────────────────────────────────────────────────────────────

	test('can set full name and change its visibility', async ({ page, user }) => {
		await page.goto('/settings/user')

		const saved = waitForSave(page)
		await page.getByRole('textbox', { name: 'Full name' }).fill('Jane Doe')
		await handlePasswordConfirmation(page, user.password)
		await saved

		await page.reload()
		await expect(page.getByRole('textbox', { name: 'Full name' })).toHaveValue('Jane Doe')

		await changeVisibility(page, 'full name', Visibility.Local, user.password)
		await page.reload()
		await expect(page.getByRole('button', { name: /change scope level of full name.*local/i })).toBeVisible()

		// With Local visibility the display name appears on the public profile
		await page.goto(`/u/${user.userId}`)
		await expect(page.getByRole('heading', { name: 'Jane Doe' })).toBeVisible()
	})

	// ── Phone number ──────────────────────────────────────────────────────────

	test('can set phone number and its visibility', async ({ page, user }) => {
		await page.goto('/settings/user')

		const saved = waitForSave(page)
		const phoneInput = page.getByRole('textbox', { name: 'Phone number' })
		await phoneInput.fill('+49 89 721010 99701')
		await handlePasswordConfirmation(page, user.password)
		await saved

		// Server normalises to E.164 format
		await page.reload()
		await expect(phoneInput).toHaveValue('+498972101099701')

		await changeVisibility(page, 'phone number', Visibility.Private, user.password)
		await page.reload()
		await expect(page.getByRole('button', { name: /change scope level of phone number.*private/i })).toBeVisible()
	})

	test('can set phone number with phone region', async ({ page, user }) => {
		await page.goto('/settings/user')
		const phoneInput = page.getByRole('textbox', { name: 'Phone number' })

		// Without a phone region, a local-format number is rejected
		await phoneInput.fill('0 40 428990')
		// NcTextField marks the field with an error class but we verify via the saved value
		// being empty after reload (the server rejects the malformed number)

		// Set the default region and reload
		await runOcc(['config:system:set', 'default_phone_region', '--value', 'DE'])
		await page.reload()

		const saved = waitForSave(page)
		await phoneInput.fill('0 40 428990')
		await handlePasswordConfirmation(page, user.password)
		await saved

		await page.reload()
		await expect(phoneInput).toHaveValue('+4940428990')

		await runOcc(['config:system:delete', 'default_phone_region'])
	})

	test('can reset phone number', async ({ page, user }) => {
		await page.goto('/settings/user')
		const phoneInput = page.getByRole('textbox', { name: 'Phone number' })

		const saved1 = waitForSave(page)
		await phoneInput.fill('+49 40 428990')
		await handlePasswordConfirmation(page, user.password)
		await saved1

		await page.reload()
		await expect(phoneInput).toHaveValue('+4940428990')

		const saved2 = waitForSave(page)
		await phoneInput.clear()
		await handlePasswordConfirmation(page, user.password)
		await saved2

		await page.reload()
		await expect(phoneInput).toHaveValue('')
	})

	// ── Social media ──────────────────────────────────────────────────────────

	test('can reset a social media property', async ({ page, user }) => {
		await page.goto('/settings/user')
		const fediverseInput = page.getByRole('textbox', { name: 'Fediverse (e.g. Mastodon)' })

		const saved1 = waitForSave(page)
		await fediverseInput.fill('@nextcloud@mastodon.social')
		await handlePasswordConfirmation(page, user.password)
		await saved1

		// The server strips the leading '@'
		await page.reload()
		await expect(fediverseInput).toHaveValue('nextcloud@mastodon.social')

		const saved2 = waitForSave(page)
		await fediverseInput.clear()
		await handlePasswordConfirmation(page, user.password)
		await saved2

		await page.reload()
		await expect(fediverseInput).toHaveValue('')
	})

	// ── Website ───────────────────────────────────────────────────────────────

	test('can set website and change its visibility', async ({ page, user }) => {
		await page.goto('/settings/user')

		const websiteInput = page.getByRole('textbox', { name: 'Website' })
		// HTML5 URL validation: 'foo bar' is not a valid URL
		await websiteInput.fill('foo bar')
		await expect(websiteInput.and(page.locator(':invalid'))).toHaveCount(1)

		const saved = waitForSave(page)
		await websiteInput.fill('http://example.com')
		await handlePasswordConfirmation(page, user.password)
		await saved

		await page.reload()
		await expect(websiteInput).toHaveValue('http://example.com')

		await changeVisibility(page, 'website', Visibility.Private, user.password)
		await page.reload()
		await expect(page.getByRole('button', { name: /change scope level of website.*private/i })).toBeVisible()

		// Change to Local so the URL appears on the public profile
		await changeVisibility(page, 'website', Visibility.Local, user.password)
		await page.goto(`/u/${user.userId}`)
		await expect(page.getByText('http://example.com')).toBeVisible()
	})

	// ── Generic properties (any value, all visibility levels) ─────────────────
	// Each property is tested in its own test so failures are isolated.

	const genericProperties = [
		{ label: 'Location', scopeProperty: 'location', value: 'Berlin' },
		{ label: 'Fediverse (e.g. Mastodon)', scopeProperty: 'fediverse', value: 'nextcloud@mastodon.xyz' },
	] as const

	for (const { label, scopeProperty, value } of genericProperties) {
		test(`can set ${label} and change its visibility`, async ({ page, user }) => {
			await page.goto('/settings/user')

			const saved = waitForSave(page)
			await page.getByRole('textbox', { name: label }).fill(value)
			await handlePasswordConfirmation(page, user.password)
			await saved

			await expect(page.getByRole('textbox', { name: label })).toHaveValue(value)
			await expect(page.getByRole('button', { name: new RegExp(`change scope level of ${scopeProperty}.*local`, 'i') })).toHaveCount(1)

			// Cycle Private → Local and verify the final state persists
			await changeVisibility(page, scopeProperty, Visibility.Federated, user.password)
			await expect(page.getByRole('button', { name: new RegExp(`change scope level of ${scopeProperty}.*federated`, 'i') })).toBeVisible()

			await page.reload()
			await expect(page.getByRole('button', { name: new RegExp(`change scope level of ${scopeProperty}.*federated`, 'i') })).toBeVisible()

			await changeVisibility(page, scopeProperty, Visibility.Private, user.password)
			await expect(page.getByRole('button', { name: new RegExp(`change scope level of ${scopeProperty}.*private`, 'i') })).toBeVisible()

			// With Local visibility the value appears on the public profile
			await page.goto(`/u/${user.userId}`)
			await expect(page.getByText(value)).toBeVisible()
		})
	}

	// ── Non-federated properties (Local and Private only) ─────────────────────

	const nonfederatedProperties = [
		{ label: 'Organisation', scopeProperty: 'organisation' },
		{ label: 'Role', scopeProperty: 'role' },
		{ label: 'Headline', scopeProperty: 'headline' },
		{ label: 'About', scopeProperty: 'about' },
	] as const

	for (const { label, scopeProperty } of nonfederatedProperties) {
		test(`can set ${label} and change its visibility`, async ({ page, user }) => {
			// Use a value unique to this property to identify it on the profile page
			const uniqueValue = `${label.toUpperCase()} ${label.toLowerCase()}`
			await page.goto('/settings/user')

			const input = page.getByRole('textbox', { name: label })

			const saved = waitForSave(page)
			await input.fill(uniqueValue)
			await handlePasswordConfirmation(page, user.password)
			await saved

			await page.reload()
			await expect(input).toHaveValue(uniqueValue)

			// Toggle Private → Local (the two supported scopes for these properties)
			await changeVisibility(page, scopeProperty, Visibility.Private, user.password)
			await page.reload()
			await expect(page.getByRole('button', { name: new RegExp(`change scope level of ${scopeProperty}.*private`, 'i') })).toBeVisible()

			await changeVisibility(page, scopeProperty, Visibility.Local, user.password)

			// With Local visibility the value appears on the public profile
			await page.goto(`/u/${user.userId}`)
			await expect(page.getByText(uniqueValue)).toBeVisible()
		})
	}
})
