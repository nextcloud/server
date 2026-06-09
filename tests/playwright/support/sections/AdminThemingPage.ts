/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

export class AdminThemingPage {
	constructor(private readonly page: Page) {}

	async open() {
		await this.page.goto('settings/admin/theming')
		await this.page.getByText('Navigation bar settings').waitFor({ state: 'visible' })
	}

	/**
	 * Resets the admin theming settings to default using HTTP request.
	 *
	 * @param request - The APIRequestContext to perform the request with admin credentials.
	 */
	async reset() {
		const tokenResponse = await this.page.request.get('/csrftoken', {
			failOnStatusCode: true,
		})
		const requestToken = (await tokenResponse.json()).token
	
		const response = await this.page.request.post('/apps/theming/ajax/undoAllChanges', {
			headers: {
				requesttoken: requestToken,
			},
		})
	
		if (!response.ok) {
			throw new Error(`Failed to reset theming settings (${response.status})`)
		}
	}

	defaultAppSwitch(): Locator {
		return this.page.getByRole('checkbox', { name: 'Use custom default app' })
	}

	defaultAppRegion(): Locator {
		return this.page.getByRole('region', { name: 'Global default app' })
	}

	defaultAppSelect(): Locator {
		return this.defaultAppRegion().getByRole('combobox')
	}

	appOrderList(): Locator {
		return this.page.getByRole('list', { name: 'Navigation bar app order' })
	}

	appOrderEntries(): Locator {
		return this.appOrderList().getByRole('listitem')
	}

	appEntry(name: string): Locator {
		return this.appOrderEntries().filter({ hasText: name })
	}

	moveUpButton(appName: string): Locator {
		return this.appEntry(appName).getByRole('button', { name: 'Move up' })
	}

	backgroundAndColorHeading(): Locator {
		return this.page.getByRole('heading', { name: 'Background and color' })
	}

	webLinkInput(): Locator {
		return this.page.getByRole('textbox', { name: /web link/i })
	}

	legalNoticeLinkInput(): Locator {
		return this.page.getByRole('textbox', { name: /legal notice link/i })
	}

	privacyPolicyLinkInput(): Locator {
		return this.page.getByRole('textbox', { name: /privacy policy link/i })
	}

	nameInput(): Locator {
		return this.page.getByRole('textbox', { name: 'Name' })
	}

	sloganInput(): Locator {
		return this.page.getByRole('textbox', { name: 'Slogan' })
	}

	undoChangesButtons(): Locator {
		return this.page.getByRole('button', { name: /undo changes/i })
	}

	removeBackgroundImageCheckbox(): Locator {
		return this.page.getByRole('checkbox', { name: /remove background image/i })
	}

	disableUserThemingCheckbox(): Locator {
		return this.page.getByRole('checkbox', { name: /disable user theming/i })
	}
}
