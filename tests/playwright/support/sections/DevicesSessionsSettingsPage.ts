/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'
import { handlePasswordConfirmation } from '../utils/password-confirmation.ts'

export class DevicesSessionsSettingsPage {
	constructor(
		private readonly page: Page,
		private readonly user: User,
	) {}

	heading(): Locator {
		return this.page.getByRole('heading', { name: 'Devices & sessions', level: 2 })
	}

	async open(): Promise<void> {
		await this.page.goto('settings/user/security')
		await expect(this.heading()).toBeVisible()
	}

	/** Product-owned id: the security page renders several unrelated tables. */
	tokenList(): Locator {
		return this.page.locator('#app-tokens-table')
	}

	tokenRows(): Locator {
		return this.tokenList().locator('tbody').getByRole('row')
	}

	/**
	 * @param name - Visible device name, "This session" for the current one
	 */
	tokenRow(name: string): Locator {
		return this.tokenRows().filter({ hasText: name })
	}

	revokeAllButton(): Locator {
		return this.page.getByRole('button', { name: 'Revoke all other sessions' })
	}

	revokeAllDialog(): Locator {
		return this.page.getByRole('dialog', { name: 'Revoke all other sessions?' })
	}

	async openRevokeAllDialog(): Promise<Locator> {
		await this.revokeAllButton().click()
		const dialog = this.revokeAllDialog()
		await expect(dialog).toBeVisible()
		return dialog
	}

	/**
	 * The DELETE only leaves the browser once the password confirmation is cleared, so
	 * the response listener has to be registered before the confirm button is used.
	 */
	async revokeAllOtherSessions(): Promise<void> {
		const dialog = await this.openRevokeAllDialog()

		const revoked = this.page.waitForResponse((r) => r.request().method() === 'DELETE'
			&& r.url().includes('/settings/personal/authtokens')
			&& r.ok())

		await dialog.getByRole('button', { name: 'Revoke all others' }).click()
		await handlePasswordConfirmation(this.page, this.user.password)
		await revoked
	}
}
