/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'

/**
 * The unified search in the top header: the combobox input and the results
 * popover it controls. Desktop layout only (the mobile header collapses to a
 * button that opens a fullscreen modal without the combobox role).
 */
export class UnifiedSearchPage {
	constructor(private readonly page: Page) {}

	private get header(): Locator {
		return this.page.locator('header#header')
	}

	/**
	 * The header search field. Its accessible name is the placeholder text, and
	 * its role is combobox (it controls the results listbox).
	 */
	input(): Locator {
		return this.header.getByRole('combobox', { name: 'Apps, files, messages, and more' })
	}

	/**
	 * The pre-typing funnel, revealed on a focused empty input. Header-scoped so the
	 * Files app's own filter controls don't match.
	 */
	filterToggle(): Locator {
		return this.header.getByRole('button', { name: 'Filters' })
	}

	/**
	 * The trailing X. Accessible name flips with state: "Clear search" with a query,
	 * "Close search" when empty. Header-scoped to skip the mobile modal's close button.
	 */
	clearButton(): Locator {
		return this.header.getByRole('button', { name: 'Clear search' })
	}

	closeButton(): Locator {
		return this.header.getByRole('button', { name: 'Close search' })
	}

	/** The filter row inside the popover (Type / Date / People). */
	filters(): Locator {
		return this.page.locator('[data-cy-unified-search-filters]')
	}

	/**
	 * The results popover. Also the aria-controls target and the host of the rows
	 * aria-activedescendant points at. Absent from the DOM while the search is closed.
	 */
	panel(): Locator {
		return this.page.locator('#unified-search-results')
	}

	/** Every selectable result row, flattened across all provider sections. */
	options(): Locator {
		return this.panel().getByRole('option')
	}

	/**
	 * The popover's polite status region (WCAG 4.1.3). It reports the search
	 * progress: "Searching …" while any provider is still in flight, then the
	 * settled outcome — "No matching results" or the result count.
	 */
	status(): Locator {
		return this.panel().getByRole('status')
	}

	/**
	 * Wait for the result set to stop changing, i.e. for every provider to have
	 * answered.
	 *
	 * Results stream in per provider and the rows re-render on every batch, which
	 * clears and re-establishes the selection in between — so a visible first row
	 * does not mean the selection is settled. Anything that reads
	 * `aria-activedescendant`, or activates the selected row with Enter, has to
	 * wait for this first: on a mid-flight list the id is momentarily absent and
	 * Enter is a no-op.
	 *
	 * The status region is the app's own settled signal, so this needs no
	 * arbitrary delay.
	 */
	async waitForSettledResults(): Promise<void> {
		// The count, not just "results": "No matching results" is a settled state too,
		// but one no selection assertion can work with. Anchored (with the template's
		// surrounding whitespace, which a regex match does not normalize away) so a
		// still-running search cannot slip through.
		await expect(this.status()).toHaveText(/^\s*\d+ results?\s*$/)
	}

	/**
	 * The id of the currently selected row, once the input names one. Waits for the
	 * attribute so a still-settling list cannot yield `null`.
	 */
	async activeOptionId(): Promise<string> {
		await expect(this.input()).toHaveAttribute('aria-activedescendant', /\S/)
		return (await this.input().getAttribute('aria-activedescendant'))!
	}

	/**
	 * A single result row addressed by its DOM id (the value the input carries in
	 * aria-activedescendant). Attribute selector so provider ids with dots or colons
	 * can't break a `#id` lookup.
	 *
	 * @param id the row's element id
	 */
	option(id: string): Locator {
		return this.page.locator(`[id="${id}"]`)
	}
}
