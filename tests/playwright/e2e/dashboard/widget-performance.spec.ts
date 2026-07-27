/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server'
import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/random-user-session.ts'

/** The endpoint the dashboard uses to load the data of its widgets. */
const WIDGET_ITEMS_API = /\/dashboard\/api\/v2\/widget-items\?widgets/

/**
 * Regression test of https://github.com/nextcloud/server/issues/48403: the
 * dashboard must only fetch data for the widgets it actually shows.
 */
test('dashboard: only loads the data of enabled widgets', async ({ page, user }) => {
	// A layout with a single widget — so exactly one data request is expected
	await runOcc(['user:setting', '--', user.userId, 'dashboard', 'layout', 'files-favorites'])

	const requests: string[] = []
	page.on('request', (request) => {
		if (WIDGET_ITEMS_API.test(request.url())) {
			requests.push(request.url())
		}
	})

	const loaded = page.waitForResponse((r) => WIDGET_ITEMS_API.test(r.url()))
	await page.goto('apps/dashboard')
	await expect(page.getByRole('heading', { name: /Good (morning|afternoon|evening|night)/ })).toBeVisible()
	await loaded

	// Give any further (unwanted) widget request time to be fired …
	await page.waitForTimeout(2000)
	// … and confirm the favorites widget was the only one that loaded data
	expect(requests).toHaveLength(1)
})
