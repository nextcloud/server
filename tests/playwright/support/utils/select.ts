/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'

/**
 * Pick an option of an `NcSelect`.
 *
 * Options are only rendered while the select is open and are teleported to the
 * document body, so they are matched on the page and not within the select.
 * `NcSelect` ellipsises option labels by splitting them into two elements, which
 * both breaks accessible name matching and makes the text of one option a
 * substring of another, so options are matched by the `title` carrying the full
 * label.
 *
 * @param page - The Playwright page object
 * @param combobox - The combobox of the select to operate
 * @param option - Label of the option to pick
 * @param search - Search term to filter the options with, if the select is searchable
 */
export async function pickSelectOption(
	page: Page,
	combobox: Locator,
	option: string,
	search?: string,
): Promise<void> {
	await combobox.scrollIntoViewIfNeeded()
	if (search === undefined) {
		await combobox.click()
	} else {
		await combobox.fill(search)
	}
	await page.getByRole('option').filter({ has: page.getByTitle(option, { exact: true }) }).click()
}

/**
 * Get the currently selected option of an open `NcSelect`.
 *
 * @param page - The Playwright page object
 */
export function selectedOption(page: Page): Locator {
	return page.getByRole('option', { selected: true })
}

/**
 * Whether an option is the selected one of its select.
 *
 * @param option - The option to check
 */
export async function isSelected(option: Locator): Promise<boolean> {
	return await option.getAttribute('aria-selected') === 'true'
}

/**
 * Assert the selected value of an `NcSelect` and close its options afterwards.
 *
 * @param page - The Playwright page object
 * @param combobox - The select to read
 * @param value - Text of the expected option
 */
export async function expectSelectedOption(page: Page, combobox: Locator, value: string | RegExp): Promise<void> {
	await combobox.click()
	await expect(selectedOption(page)).toContainText(value)
	await page.keyboard.press('Escape')
}
