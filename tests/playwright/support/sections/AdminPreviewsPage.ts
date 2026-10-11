/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

export class AdminPreviewsPage {
	constructor(private readonly page: Page) {}

	async open() {
		await this.page.goto('settings/admin/previews')
		await this.enablePreviewsSwitch().waitFor({ state: 'visible' })
	}

	enablePreviewsSwitch(): Locator {
		return this.page.getByRole('switch', { name: 'Enable previews' })
	}

	providersTable(): Locator {
		return this.page.getByRole('table', { name: 'Preview providers' })
	}

	providerRows(): Locator {
		return this.providersTable().getByRole('row')
	}

	providerSwitch(name: string): Locator {
		return this.providersTable().getByRole('switch', { name, exact: true })
	}

	moveEarlierButton(name: string): Locator {
		return this.providersTable().getByRole('button', { name: `Try ${name} earlier` })
	}

	resetProvidersButton(): Locator {
		return this.page.getByRole('button', { name: 'Reset to default providers' })
	}

	maxWidthField(): Locator {
		return this.page.getByRole('spinbutton', { name: 'Maximum width (pixels)' })
	}

	/**
	 * Position of a provider in the table, the try-order for enabled ones
	 *
	 * @param name - The provider name
	 */
	async providerIndex(name: string): Promise<number> {
		const names = await this.providersTable().getByRole('rowheader').allTextContents()
		return names.map((text) => text.trim()).indexOf(name)
	}
}
