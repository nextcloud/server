/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'

export class PasswordSettingsPage {
	constructor(private readonly page: Page) {}

	heading(): Locator {
		// "Passwordless Authentication" is on the same page
		return this.page.getByRole('heading', { name: 'Password', level: 2, exact: true })
	}

	async open(): Promise<void> {
		await this.page.goto('settings/user/security')
		await expect(this.heading()).toBeVisible()
	}

	currentPasswordInput(): Locator {
		return this.page.getByLabel('Current password')
	}

	newPasswordInput(): Locator {
		return this.page.getByLabel('New password')
	}

	async changePassword(currentPassword: string, newPassword: string): Promise<void> {
		await this.currentPasswordInput().fill(currentPassword)
		await this.newPasswordInput().fill(newPassword)
		await this.page.getByRole('button', { name: 'Change password' }).click()
	}
}
