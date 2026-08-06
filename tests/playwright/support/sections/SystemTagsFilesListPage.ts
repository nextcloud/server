/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator } from '@playwright/test'

import { expect } from '@playwright/test'
import { FilesListPage } from './FilesListPage.ts'

/**
 * Extension of {@link FilesListPage} for tests involving the SystemTags app.
 * Adds locators, actions, and assertion helpers for the SystemTagPicker dialog
 * and the inline collaborative-tags column.
 */
export class SystemTagsFilesListPage extends FilesListPage {
	/**
	 * The "Manage tags" dialog (SystemTagPicker).
	 */
	getTagPicker(): Locator {
		return this.page.getByRole('dialog', { name: 'Manage tags' })
	}

	/**
	 * The inline collaborative-tags list rendered in the file row.
	 * The overflow indicator (e.g. "+2") has role="presentation" and is excluded
	 * by getByRole('listitem'), so the accessible listitem count always equals the
	 * number of actual tags regardless of whether an overflow indicator is shown.
	 */
	getInlineTagsForFile(filename: string): Locator {
		return this.getRowForFile(filename).getByRole('list', { name: /collaborative tags/i })
	}

	/**
	 * Opens the SystemTagPicker for a single file via its row action and waits
	 * for the tags PROPFIND to complete before returning the picker locator.
	 */
	async openTagPickerForFile(filename: string): Promise<Locator> {
		const tagsListLoaded = this.page.waitForResponse((r) => r.url().includes('/remote.php/dav/systemtags/') && r.request().method() === 'PROPFIND' && !r.url().includes('/files'))
		await this.triggerActionForFile(filename, 'systemtags:bulk')
		await tagsListLoaded
		const picker = this.getTagPicker()
		await picker.waitFor({ state: 'visible' })
		return picker
	}

	/**
	 * Opens the SystemTagPicker for the current selection via the selection
	 * actions bar and waits for the tags PROPFIND to complete before returning
	 * the picker locator.
	 */
	async openTagPickerForSelection(): Promise<Locator> {
		const tagsListLoaded = this.page.waitForResponse((r) => r.url().includes('/remote.php/dav/systemtags/') && r.request().method() === 'PROPFIND' && !r.url().includes('/files'))
		await this.triggerSelectionAction('systemtags:bulk')
		await tagsListLoaded
		const picker = this.getTagPicker()
		await picker.waitFor({ state: 'visible' })
		return picker
	}

	/**
	 * Types a new tag name into the already-open picker, asserts no existing match,
	 * creates the tag via the "Create new tag" button, verifies it is checked.
	 */
	async createNewTagInPicker(tagName: string): Promise<void> {
		const picker = this.getTagPicker()
		await picker.waitFor({ state: 'visible' })

		await picker.getByLabel(/Search.*tag/i).fill(tagName)
		await expect(picker.getByRole('checkbox')).toHaveCount(0)

		const createTagResponse = this.page.waitForResponse((r) => r.url().includes('/remote.php/dav/systemtags') && !r.url().includes('/files') && r.request().method() === 'POST')
		await picker.getByRole('button', { name: /Create new tag/i }).click()
		await createTagResponse

		await expect(picker.getByRole('checkbox', { name: tagName })).toBeChecked()
	}

	async selectTagInPicker(tagName: string): Promise<void> {
		const picker = this.getTagPicker()
		await picker.waitFor({ state: 'visible' })

		await picker.getByLabel(/Search.*tag/i).fill(tagName)
		await expect(picker.getByRole('checkbox', { name: new RegExp(tagName, 'i') })).toHaveCount(1)
		await picker.getByRole('checkbox', { name: new RegExp(tagName, 'i') }).check({ force: true })
	}

	/**
	 * Unassign a tag in the already-open picker. Filters the list to the single
	 * matching tag first: force-clicking a NcCheckboxRadioSwitch's visually-hidden
	 * input is only reliable when the row is isolated and unobstructed — toggling
	 * within the full, scrollable list can leave the state unchanged. Asserts the
	 * checkbox actually cleared so a missed toggle fails loudly.
	 */
	async unselectTagInPicker(tagName: string): Promise<void> {
		const picker = this.getTagPicker()
		await picker.waitFor({ state: 'visible' })

		await picker.getByLabel(/Search.*tag/i).fill(tagName)
		const checkbox = picker.getByRole('checkbox', { name: new RegExp(tagName, 'i') })
		await expect(checkbox).toHaveCount(1)
		await checkbox.uncheck({ force: true })
		await expect(checkbox).not.toBeChecked()
	}

	/**
	 * Clicks Apply on the already-open picker and waits for the dialog to close.
	 */
	async applyTagPicker(): Promise<void> {
		const picker = this.getTagPicker()
		await picker.getByRole('button', { name: 'Apply' }).click()
		await expect(picker).not.toBeVisible({ timeout: 10_000 })
	}

	/**
	 * Asserts that the inline tag list for a file shows exactly the expected tags.
	 */
	async expectInlineTagsForFile(filename: string, expectedTags: string[]): Promise<void> {
		const tagList = this.getInlineTagsForFile(filename)

		if (expectedTags.length === 0) {
			await expect(tagList).toHaveCount(0)
			return
		}

		await expect(tagList.getByRole('listitem')).toHaveCount(expectedTags.length)
		for (const tag of expectedTags) {
			await expect(tagList).toContainText(tag)
		}
	}
}
