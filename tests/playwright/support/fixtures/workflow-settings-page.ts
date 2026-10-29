/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { APIRequestContext } from '@playwright/test'

import { WorkflowSettingsPage } from '../sections/WorkflowSettingsPage.ts'
import { clearRules } from '../utils/workflowengine.ts'
import { test as adminTest } from './admin-session.ts'

type WorkflowFixtures = {
	/**
	 * A request context authenticated as admin via basic auth. Logging in with
	 * the actual password marks the session as freshly confirmed, so the
	 * password-confirmation guarded write endpoints accept it without a dialog.
	 */
	adminRequest: APIRequestContext
	/** The flow settings page. Global flows are removed again after the test. */
	workflowSettings: WorkflowSettingsPage
}

/**
 * Admin session plus the {@link WorkflowSettingsPage} page object. Configuring
 * instance-wide flows is an admin task, and the specs run in the serial
 * `admin-settings` project because the flows are instance-wide state.
 */
export const test = adminTest.extend<WorkflowFixtures>({
	adminRequest: async ({ playwright, baseURL }, use) => {
		const context = await playwright.request.newContext({
			baseURL,
			// send: 'always' — the OCS API does not issue a Basic auth challenge,
			// so the credentials have to be sent preemptively
			httpCredentials: { username: 'admin', password: 'admin', send: 'always' },
		})
		await use(context)
		await context.dispose()
	},

	workflowSettings: async ({ page, adminRequest }, use) => {
		await clearRules(adminRequest, 'global')
		await use(new WorkflowSettingsPage(page))
		await clearRules(adminRequest, 'global')
	},
})

export { expect } from '../matchers.ts'
