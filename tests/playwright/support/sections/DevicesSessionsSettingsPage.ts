/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'
import { awaitPasswordGuardedRequest } from '../utils/password-confirmation.ts'

export class DevicesSessionsSettingsPage {
	constructor(private readonly page: Page) {}

	heading(): Locator {
		return this.page.getByRole('heading', { name: 'Devices & sessions', level: 2 })
	}

	async open(): Promise<void> {
		await this.page.goto('settings/user/security')
		await expect(this.heading()).toBeVisible()
	}

	/** Product-owned id: the security page renders several unrelated tables. */
	tokenList(): Locator {
		return this.page.locator('#app-tokens-table')
	}

	tokenRows(): Locator {
		return this.tokenList().locator('tbody').getByRole('row')
	}

	/**
	 * @param name - Visible device name, "This session" for the current one
	 */
	tokenRow(name: string): Locator {
		return this.tokenRows().filter({ hasText: name })
	}

	revokeAllButton(): Locator {
		return this.page.getByRole('button', { name: 'Revoke all other sessions' })
	}

	revokeAllDialog(): Locator {
		return this.page.getByRole('dialog', { name: 'Revoke all other sessions?' })
	}

	async openRevokeAllDialog(): Promise<Locator> {
		await this.revokeAllButton().click()
		const dialog = this.revokeAllDialog()
		await expect(dialog).toBeVisible()
		return dialog
	}

	/**
	 * The DELETE only leaves the browser once the password confirmation is cleared, so
	 * the response listener has to be registered before the confirm button is used.
	 */
	async revokeAllOtherSessions(): Promise<void> {
		const dialog = await this.openRevokeAllDialog()

		const revoked = this.page.waitForResponse((r) => r.request().method() === 'DELETE'
			&& r.url().includes('/settings/personal/authtokens')
			&& r.ok())

		await dialog.getByRole('button', { name: 'Revoke all others' }).click()
		await awaitPasswordGuardedRequest(this.page, revoked)
	}

	accessDialog(name: 'Create new app password' | 'Edit access'): Locator {
		return this.page.getByRole('dialog', { name })
	}

	/**
	 * @param name - Name of the new app password
	 * @param access - Label of the access preset to pick
	 */
	async createAppPassword(name: string, access: string): Promise<void> {
		await this.page.getByRole('button', { name: 'Create new app password' }).click()
		const dialog = this.accessDialog('Create new app password')
		await dialog.getByRole('textbox', { name: 'App name' }).fill(name)
		await dialog.getByRole('radio', { name: access }).check({ force: true })

		const created = this.page.waitForResponse((r) => r.request().method() === 'POST'
			&& r.url().includes('/settings/personal/authtokens')
			&& r.ok())
		await dialog.getByRole('button', { name: 'Create' }).click()
		await awaitPasswordGuardedRequest(this.page, created)

		const setup = this.page.getByRole('dialog', { name: 'New app password', exact: true })
		await expect(setup).toBeVisible()
		await setup.getByRole('button', { name: 'Close' }).click()
		await expect(setup).toBeHidden()
	}

	/**
	 * @param row - Visible name of the app password
	 * @param access - Label of the access preset to pick
	 */
	async editAccess(row: string, access: string): Promise<void> {
		await this.tokenRow(row).getByRole('button', { name: 'Device settings' }).click()
		await this.page.getByRole('menuitem', { name: 'Edit access' }).click()
		const dialog = this.accessDialog('Edit access')
		await dialog.getByRole('radio', { name: access }).check({ force: true })

		const saved = this.page.waitForResponse((r) => r.request().method() === 'PUT'
			&& r.url().includes('/settings/personal/authtokens/')
			&& r.ok())
		await dialog.getByRole('button', { name: 'Save' }).click()
		await awaitPasswordGuardedRequest(this.page, saved)
		await expect(dialog).toBeHidden()
	}
}
