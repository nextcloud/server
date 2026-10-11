/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/public-share-auth-page.ts'

test.describe('core: Password-protected share', () => {
	test.beforeEach(async ({ protectedShare, publicShareAuth }) => {
		await publicShareAuth.open(protectedShare.url)
	})

	test('asks for the password', async ({ publicShareAuth }) => {
		await expect(publicShareAuth.heading()).toBeVisible()
		await expect(publicShareAuth.passwordField()).toBeFocused()
		await expect(publicShareAuth.errorNote()).toHaveCount(0)
	})

	test('shows an error and keeps the form for a wrong password', async ({ publicShareAuth }) => {
		await publicShareAuth.submit('wrong password')

		await expect(publicShareAuth.errorNote()).toBeVisible()
		await expect(publicShareAuth.heading()).toBeVisible()
		await expect(publicShareAuth.passwordField()).toBeVisible()
	})

	test('opens the share with the correct password', async ({ page, protectedShare, publicShareAuth, publicShare }) => {
		const request = page.waitForRequest((request) => request.method() === 'POST' && request.url().includes(`/s/${protectedShare.token}/authenticate`))
		await publicShareAuth.submit(protectedShare.password)

		const body = new URLSearchParams((await request).postData() ?? '')
		expect(body.get('password')).toBe(protectedShare.password)
		expect(body.get('sharingToken')).toBe(protectedShare.token)
		expect(body.get('requesttoken')).toBeTruthy()

		await expect(publicShareAuth.heading()).toHaveCount(0)
		await expect(publicShare.header()).toBeVisible()
		await expect(page.getByText(protectedShare.fileName).first()).toBeVisible()
	})
})
