/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

/**
 * The admin "External storage" settings section
 * (Settings → Administration → External storage).
 */
export class ExternalStorageSettingsPage {
	constructor(private readonly page: Page) {}

	async open(): Promise<void> {
		await this.page.goto('settings/admin/externalstorages')
		await this.table().waitFor({ state: 'visible' })
	}

	heading(): Locator {
		return this.page.getByRole('heading', { name: /External storage/, level: 2 })
	}

	/** The external storages table. */
	table(): Locator {
		return this.page.getByRole('table', { name: 'External storages' })
	}

	/** The table body, where the configured storages are listed as rows. */
	tableBody(): Locator {
		return this.table().locator('tbody')
	}

	/** The configured-storage rows (excludes the header row). */
	rows(): Locator {
		return this.tableBody().getByRole('row')
	}

	/** Open the "Add storage" dialog and wait for it to appear. */
	async openAddDialog(): Promise<Locator> {
		await this.page.getByRole('button', { name: 'Add external storage' }).click()
		const dialog = this.dialog()
		await dialog.waitFor({ state: 'visible' })
		return dialog
	}

	dialog(): Locator {
		return this.page.getByRole('dialog', { name: 'Add storage' })
	}

	/**
	 * An NcSelect combobox inside the dialog, addressed by its (visible) label.
	 * NcSelect renders a `<label for>` bound to the combobox input, so the input's
	 * accessible name is the label text.
	 */
	comboBox(name: string | RegExp): Locator {
		return this.dialog().getByRole('combobox', { name })
	}

	/**
	 * Pick an option in one of the dialog's NcSelect comboboxes. The listbox is
	 * teleported to `<body>`, so the option is queried at page level.
	 *
	 * @param comboBoxName - The label of the combobox to open
	 * @param optionName - The accessible name of the option to pick
	 */
	async selectComboBoxOption(comboBoxName: string | RegExp, optionName: string | RegExp): Promise<void> {
		await this.comboBox(comboBoxName).click()
		// No force: let Playwright auto-wait for the (teleported) option to be
		// actionable. Force-clicking races the dropdown open and silently drops the
		// selection, leaving dependent fields (e.g. Authentication) disabled.
		const option = this.page.getByRole('option', { name: optionName, exact: typeof optionName === 'string' })
		await option.click()
	}

	createButton(): Locator {
		return this.dialog().getByRole('button', { name: 'Create' })
	}
}
