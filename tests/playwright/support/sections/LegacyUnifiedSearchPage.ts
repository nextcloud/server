/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

/**
 * The legacy unified search in the top header, offered instead of the current
 * one while `unified_search.enabled` is set: a header menu with a search field
 * and results grouped by provider.
 */
export class LegacyUnifiedSearchPage {
	constructor(private readonly page: Page) {}

	private get header(): Locator {
		return this.page.locator('header#header')
	}

	/** The header button that opens the search menu. */
	trigger(): Locator {
		return this.header.getByRole('button', { name: 'Search', exact: true })
	}

	input(): Locator {
		return this.header.getByRole('textbox', { name: 'Search', exact: true })
	}

	/** The results of one provider, e.g. "Files". */
	results(provider: string): Locator {
		return this.header.getByRole('list', { name: provider })
	}

	/** The results of one provider whose title matches. */
	resultLinks(provider: string, title: RegExp): Locator {
		return this.results(provider).getByRole('link', { name: title })
	}

	loadMoreButton(provider: string): Locator {
		return this.results(provider).getByRole('link', { name: 'Load more results' })
	}

	async search(query: string): Promise<void> {
		await this.trigger().click()
		await this.input().fill(query)
	}
}
