/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server'
import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/admin-theming-page.ts'

const admin = new User('admin', 'admin')

test.describe('Admin theming branding settings', () => {
	test.describe.configure({ mode: 'serial' })

	test.beforeEach(async ({ adminThemingPage }) => {
		await adminThemingPage.reset()
		await adminThemingPage.open()
	})

	test('Set project links and verify persisted values', async ({ adminThemingPage, page }) => {
		await expect(adminThemingPage.webLinkInput()).toHaveAttribute('type', 'url')
		await expect(adminThemingPage.legalNoticeLinkInput()).toHaveAttribute('type', 'url')
		await expect(adminThemingPage.privacyPolicyLinkInput()).toHaveAttribute('type', 'url')

		await adminThemingPage.webLinkInput().fill('http://example.com/path?query#fragment')
		await Promise.all([
			page.waitForResponse((response) => response.url().includes('/apps/theming/ajax/updateStylesheet') && response.request().method() === 'POST'),
			adminThemingPage.webLinkInput().press('Enter'),
		])

		await adminThemingPage.legalNoticeLinkInput().fill('http://example.com/legal?query#fragment')
		await Promise.all([
			page.waitForResponse((response) => response.url().includes('/apps/theming/ajax/updateStylesheet') && response.request().method() === 'POST'),
			adminThemingPage.legalNoticeLinkInput().press('Enter'),
		])

		await adminThemingPage.privacyPolicyLinkInput().fill('http://privacy.local/path?query#fragment')
		await Promise.all([
			page.waitForResponse((response) => response.url().includes('/apps/theming/ajax/updateStylesheet') && response.request().method() === 'POST'),
			adminThemingPage.privacyPolicyLinkInput().press('Enter'),
		])

		await page.reload()
		await expect(adminThemingPage.webLinkInput()).toHaveValue('http://example.com/path?query#fragment')
		await expect(adminThemingPage.legalNoticeLinkInput()).toHaveValue('http://example.com/legal?query#fragment')
		await expect(adminThemingPage.privacyPolicyLinkInput()).toHaveValue('http://privacy.local/path?query#fragment')
	})

	test('Set and undo login fields', async ({ adminThemingPage, page }) => {
		const name = 'ABCdef123'
		const url = 'https://example.com'
		const slogan = 'Testing is fun'

		await Promise.all([
			page.waitForResponse((response) => response.url().includes('/apps/theming/ajax/updateStylesheet') && response.request().method() === 'POST'),
			adminThemingPage.nameInput().fill(name),
		])
		await adminThemingPage.nameInput().press('Enter')

		await Promise.all([
			page.waitForResponse((response) => response.url().includes('/apps/theming/ajax/updateStylesheet') && response.request().method() === 'POST'),
			adminThemingPage.webLinkInput().fill(url),
		])
		await adminThemingPage.webLinkInput().press('Enter')

		await Promise.all([
			page.waitForResponse((response) => response.url().includes('/apps/theming/ajax/updateStylesheet') && response.request().method() === 'POST'),
			adminThemingPage.sloganInput().fill(slogan),
		])
		await adminThemingPage.sloganInput().press('Enter')

		await expect(adminThemingPage.undoChangesButtons()).toHaveCount(3)

		for (let index = 0; index < 3; index++) {
			await Promise.all([
				page.waitForResponse((response) => response.url().includes('/apps/theming/ajax/undoChanges') && response.request().method() === 'POST'),
				adminThemingPage.undoChangesButtons().first().click(),
			])
		}
		await expect(adminThemingPage.undoChangesButtons()).toHaveCount(0)
	})

	test('Web link corner cases', async ({ adminThemingPage, page }) => {
		await setUrlFieldAndWait(page, adminThemingPage.webLinkInput(), 'http://example.com/%22path%20with%20space%22')
		await page.reload()
		await expect(adminThemingPage.webLinkInput()).toHaveValue('http://example.com/%22path%20with%20space%22')

		await setUrlFieldAndWait(page, adminThemingPage.webLinkInput(), 'http://example.com/"path"')
		await page.reload()
		await expect(adminThemingPage.webLinkInput()).toHaveValue('http://example.com/%22path%22')

		await setUrlFieldAndWait(page, adminThemingPage.webLinkInput(), 'http://example.com/"the%20path"')
		await page.reload()
		await expect(adminThemingPage.webLinkInput()).toHaveValue('http://example.com/%22the%20path%22')
	})
})

async function setUrlFieldAndWait(page: import('@playwright/test').Page, locator: import('@playwright/test').Locator, value: string) {
	await locator.fill(value)
	await Promise.all([
		page.waitForResponse((response) => response.url().includes('/apps/theming/ajax/updateStylesheet') && response.request().method() === 'POST'),
		locator.press('Enter'),
	])
}
