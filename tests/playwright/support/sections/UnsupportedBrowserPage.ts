/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

/**
 * The warning page unsupported browsers are redirected to.
 */
export class UnsupportedBrowserPage {
	constructor(private readonly page: Page) {}

	/**
	 * Open the warning page.
	 *
	 * @param redirectPath - The path to continue to, as the redirect passes it
	 */
	async open(redirectPath?: string): Promise<void> {
		const query = redirectPath === undefined
			? ''
			: `?redirect_url=${encodeURIComponent(Buffer.from(redirectPath).toString('base64'))}`
		await this.page.goto(`/unsupported${query}`)
	}

	heading(): Locator {
		return this.page.getByRole('heading', { name: 'Your browser is not supported.' })
	}

	continueButton(): Locator {
		return this.page.getByRole('button', { name: 'Continue with this unsupported browser' })
	}
}
