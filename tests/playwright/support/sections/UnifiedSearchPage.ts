/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

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
	 * A single result row addressed by its DOM id (the value the input carries in
	 * aria-activedescendant). Attribute selector so provider ids with dots or colons
	 * can't break a `#id` lookup.
	 *
	 * @param id the row's element id
	 */
	option(id: string): Locator {
		return this.page.locator(`[id="${id}"]`)
	}

	/**
	 * The "More from {name}" overflow control: a category heading rendered as a button
	 * when the category has more than the aggregate cap of rows. Opens the detail view.
	 *
	 * @param name the category name, e.g. "Files"
	 */
	moreFrom(name: string): Locator {
		return this.panel().getByRole('button', { name: `More from ${name}` })
	}

	/** The detail view's back control, which returns to the aggregate list. */
	detailBack(): Locator {
		return this.panel().getByRole('button', { name: 'Back to all results' })
	}

	/**
	 * The heading titling the detail view (shown once a category is expanded).
	 *
	 * @param name the category name
	 */
	detailHeading(name: string): Locator {
		return this.panel().getByRole('heading', { name })
	}

	/** The detail view's pagination control, present while the category has more pages. */
	loadMore(): Locator {
		return this.panel().getByRole('button', { name: 'Load more results' })
	}
}
