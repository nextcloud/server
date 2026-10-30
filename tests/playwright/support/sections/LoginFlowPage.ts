/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

/**
 * The pages a client (desktop, mobile) opens to obtain an app password: the
 * login prompt, the grant page and, for login flow v2, the final page.
 */
export class LoginFlowPage {
	constructor(private readonly page: Page) {}

	/**
	 * Open the login flow v1 like a client does: only the first request carries
	 * the API header and names the client through its user agent.
	 *
	 * @param clientName - The user agent the client sends
	 */
	async openV1(clientName: string): Promise<void> {
		await this.page.route(/\/login\/flow$/, async (route) => {
			await route.continue({
				headers: {
					...route.request().headers(),
					'OCS-APIREQUEST': 'true',
					'user-agent': clientName,
				},
			})
		}, { times: 1 })
		await this.page.goto('/login/flow')
	}

	/**
	 * Open the login URL a client received from `POST /login/v2`.
	 *
	 * @param loginUrl - The `login` URL of the response
	 */
	async openV2(loginUrl: string): Promise<void> {
		await this.page.goto(loginUrl)
	}

	heading(name: 'Connect to your account' | 'Account access' | 'Account connected'): Locator {
		return this.page.getByRole('heading', { name })
	}

	/** The info note describing who is granted access to what. */
	note(text: string | RegExp): Locator {
		return this.page.getByRole('note').filter({ hasText: text })
	}

	/** The link to the login page, styled (and on some versions exposed) as a button. */
	logInButton(): Locator {
		return this.page.getByRole('link', { name: 'Log in', exact: true })
			.or(this.page.getByRole('button', { name: 'Log in', exact: true }))
	}

	grantAccessButton(): Locator {
		return this.page.getByRole('button', { name: 'Grant access' })
	}
}
