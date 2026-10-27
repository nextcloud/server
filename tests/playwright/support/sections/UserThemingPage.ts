/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

export class UserThemingPage {
	constructor(private readonly page: Page) {}

	async open() {
		await this.page.goto('settings/user/theming')
		await this.page.getByRole('heading', { name: /Navigation bar settings/ }).waitFor({ state: 'visible' })
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
		return this.appEntry(appName).getByRole('button', { name: 'Move up', includeHidden: true })
	}
}
