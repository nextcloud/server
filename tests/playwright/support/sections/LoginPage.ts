/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

export class LoginPage {
	constructor(private readonly page: Page) {}

	usernameInput(): Locator {
		// Label is "Account name" or "Account name or email" depending on config
		return this.page.getByLabel(/Account name/)
	}

	passwordInput(): Locator {
		return this.page.getByLabel('Password').and(this.page.locator('input'))
	}

	submitButton(): Locator {
		return this.page.getByRole('button', { name: 'Log in', exact: true })
	}

	async goto(): Promise<void> {
		await this.page.goto('/login')
	}

	async login(userId: string, password: string): Promise<void> {
		await this.usernameInput().fill(userId)
		await this.passwordInput().fill(password)
		await this.submitButton().click()
	}
}
