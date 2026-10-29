/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/random-user-session.ts'
import { WorkflowSettingsPage } from '../../support/sections/WorkflowSettingsPage.ts'

test.describe('Flow personal settings', () => {
	test('offers nothing while no flow is available to users', async ({ page }) => {
		const workflowSettings = new WorkflowSettingsPage(page)
		await workflowSettings.open('personal')

		// the only operation this repository ships is admin scoped
		await expect(workflowSettings.noFlowsInstalled()).toBeVisible()
		await expect(workflowSettings.askAdministratorHint()).toBeVisible()

		// without an available flow there is nothing to configure either
		await expect(workflowSettings.configuredFlowsHeading()).toBeHidden()
	})
})
