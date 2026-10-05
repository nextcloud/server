/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

/**
 * The page a guest sees when opening a password-protected share link, before
 * the share itself is shown.
 */
export class PublicShareAuthPage {
	constructor(private readonly page: Page) {}

	/** Open a share URL (as returned by `createLinkShare`). */
	async open(url: string): Promise<void> {
		await this.page.goto(url)
	}

	heading(): Locator {
		return this.page.getByRole('heading', { name: 'This share is password-protected' })
	}

	passwordField(): Locator {
		return this.page.getByLabel('Password').and(this.page.locator('input'))
	}

	submitButton(): Locator {
		return this.page.getByRole('button', { name: 'Submit' })
	}

	/** The error shown after a wrong password was submitted. */
	errorNote(): Locator {
		return this.page.getByRole('alert').filter({ hasText: 'The password is wrong or expired.' })
	}

	async submit(password: string): Promise<void> {
		await this.passwordField().fill(password)
		await this.submitButton().click()
	}
}
