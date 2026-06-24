/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'
import { handlePasswordConfirmation } from '../utils/password-confirmation.ts'

/**
 * Page object for the Admin Users Management page (/settings/users).
 *
 * Selector strategy:
 * - Prefer role / label / text selectors.
 * - `data-cy-user-row` and `data-cy-user-list` are the only data-attribute
 *   selectors used — the virtual-scroll list and individual rows have no
 *   semantic ARIA alternative.
 * - `data-cy-users-settings-navigation-groups` is used for the custom groups
 *   list because the list has no distinct accessible name.
 */
export class SettingsUsersPage {
	constructor(private readonly page: Page) {}

	async open(): Promise<void> {
		await this.page.goto('/settings/users')
		await this.userList().waitFor({ state: 'visible' })
	}

	// ── Sidebar navigation ──────────────────────────────────────────────────

	navigation(): Locator {
		return this.page.getByRole('navigation', { name: 'Account management' })
	}

	/** Click a named link in the account management sidebar. */
	async navigateTo(name: string | RegExp): Promise<void> {
		await this.navigation().getByRole('link', { name }).click()
	}

	/** The custom groups section in the sidebar navigation. */
	customGroupsList(): Locator {
		return this.page.locator('[data-cy-users-settings-navigation-groups="custom"]')
	}

	groupListItem(groupName: string): Locator {
		return this.customGroupsList().getByRole('listitem').filter({ hasText: groupName })
	}

	// ── User list ────────────────────────────────────────────────────────────

	userList(): Locator {
		return this.page.locator('[data-cy-user-list]')
	}

	userRow(userId: string): Locator {
		return this.page.locator(`[data-cy-user-row="${userId}"]`)
	}

	// ── Dialogs ──────────────────────────────────────────────────────────────

	/** Open the "New account" dialog and wait for it to appear. */
	async openNewUserDialog(): Promise<void> {
		await this.page.getByRole('navigation')
			.getByRole('button', { name: 'New account' })
			.click()
		await this.newUserDialog().waitFor({ state: 'visible' })
	}

	newUserDialog(): Locator {
		return this.page.getByRole('dialog', { name: 'New account' })
	}

	/** Open the edit dialog for `userId` by clicking its inline Edit button. */
	async openEditDialog(userId: string): Promise<void> {
		await this.userRow(userId).getByRole('button', { name: 'Edit' }).click()
		await this.editUserDialog().waitFor({ state: 'visible' })
	}

	editUserDialog(): Locator {
		return this.page.getByRole('dialog', { name: 'Edit account' })
	}

	/** Save and close the currently open edit dialog. */
	async saveEditDialog(): Promise<void> {
		const dialog = this.editUserDialog()
		const button = dialog.getByRole('button', { name: 'Save' })
		await button.focus()
		await button.click({ force: true })
		await handlePasswordConfirmation(this.page)
		await dialog.waitFor({ state: 'hidden' })
	}

	/** Open the actions dropdown for `userId`. */
	async openActionsMenu(userId: string): Promise<void> {
		const button = this.userRow(userId).getByRole('button', { name: 'Toggle account actions menu' })
		await button.click()
		await expect(button).toHaveAttribute('aria-controls')
		await expect(this.page.getByRole('menu').and(this.page.locator('#' + await button.getAttribute('aria-controls')))).toBeVisible()
	}

	/** Open the "Account management settings" dialog. */
	async openSettingsDialog(): Promise<void> {
		await this.page.getByRole('button', { name: 'Account management settings' }).click()
		await this.settingsDialog().waitFor({ state: 'visible' })
	}

	settingsDialog(): Locator {
		return this.page.getByRole('dialog', { name: 'Account management settings' })
	}

	/** Close the "Account management settings" dialog. */
	async closeSettingsDialog(): Promise<void> {
		await this.settingsDialog().getByRole('button', { name: 'Close' }).click()
		await this.settingsDialog().waitFor({ state: 'hidden' })
	}
}
