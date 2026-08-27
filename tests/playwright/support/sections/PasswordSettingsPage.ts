/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'

export class PasswordSettingsPage {
	constructor(
		private readonly page: Page,
		private readonly user: User,
	) {}

	heading(): Locator {
		return this.page.getByRole('heading', { name: 'Password', exact: true, level: 2 })
	}

	async open(): Promise<void> {
		await this.page.goto('settings/user/security')
		await expect(this.heading()).toBeVisible()
	}

	/** Password inputs carry no ARIA role, so they are addressed by their label. */
	private field(label: string): Locator {
		return this.page.getByLabel(label).and(this.page.locator('input'))
	}

	private revokeOtherSessionsCheckbox(): Locator {
		return this.page.getByRole('checkbox', { name: 'Sign out all other devices and apps' })
	}

	/**
	 * @param newPassword - Password to set
	 * @param revokeOtherSessions - Tick the opt-in that signs out every other device
	 */
	async changePassword(newPassword: string, revokeOtherSessions = false): Promise<void> {
		await this.field('Current password').fill(this.user.password)
		await this.field('New password').fill(newPassword)

		if (revokeOtherSessions) {
			// NcCheckboxRadioSwitch hides the native input behind its label.
			await this.revokeOtherSessionsCheckbox().check({ force: true })
		}

		const changed = this.page.waitForResponse((r) => r.request().method() === 'POST'
			&& r.url().includes('/settings/personal/changepassword')
			&& r.ok())
		await this.page.getByRole('button', { name: 'Change password' }).click()
		await changed
	}
}
