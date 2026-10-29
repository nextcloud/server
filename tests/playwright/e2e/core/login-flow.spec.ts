/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/random-user.ts'
import { LoginFlowPage } from '../../support/sections/LoginFlowPage.ts'
import { LoginPage } from '../../support/sections/LoginPage.ts'

test.describe('core: Login flow', () => {
	test('v1 grants a client access and hands it the app password', async ({ page, user }) => {
		const loginFlow = new LoginFlowPage(page)
		await loginFlow.openV1('Playwright Client')

		await expect(loginFlow.heading('Connect to your account')).toBeVisible()
		await expect(loginFlow.note('"Playwright Client"')).toBeVisible()

		await loginFlow.logInButton().click()
		await new LoginPage(page).login(user.userId, user.password)

		await expect(loginFlow.heading('Account access')).toBeVisible()
		await expect(loginFlow.note(`Currently logged in as ${user.userId} (${user.userId}).`)).toBeVisible()

		const response = page.waitForResponse((response) => response.request().method() === 'POST' && /\/login\/flow$/.test(response.url()))
		await loginFlow.grantAccessButton().click()

		const location = (await response).headers().location
		expect(location).toMatch(/^nc:\/\/login\/server:/)
		expect(location).toContain(`&user:${encodeURIComponent(user.userId)}&password:`)
	})

	test('v2 grants a client access and hands it the app password', async ({ page, request, user }) => {
		const init = await request.post('login/v2')
		expect(init.ok()).toBe(true)
		const { login, poll } = await init.json() as { login: string, poll: { token: string, endpoint: string } }

		const loginFlow = new LoginFlowPage(page)
		await loginFlow.openV2(login)
		await expect(loginFlow.heading('Connect to your account')).toBeVisible()

		await loginFlow.logInButton().click()
		await new LoginPage(page).login(user.userId, user.password)

		await expect(loginFlow.heading('Account access')).toBeVisible()
		await loginFlow.grantAccessButton().click()

		await expect(loginFlow.heading('Account connected')).toBeVisible()
		await expect(loginFlow.note('Your client should now be connected!')).toBeVisible()

		const credentials = await request.post(poll.endpoint, { form: { token: poll.token } })
		expect(credentials.ok()).toBe(true)
		expect(await credentials.json()).toMatchObject({ loginName: user.userId })
	})
})
