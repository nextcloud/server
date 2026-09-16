/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'

/**
 * The database engines offered by the installation wizard, keyed by their
 * visible (accessible) radio label. `oci` (Oracle) is only offered when the
 * server is configured to allow all databases.
 */
export type DatabaseName = 'SQLite' | 'MySQL/MariaDB' | 'PostgreSQL' | 'Oracle'

/** Connection details for a non-SQLite database backend. */
export interface DatabaseConnection {
	user: string
	password: string
	name: string
	host: string
}

/**
 * The Nextcloud installation wizard (`core/src/views/Setup.vue`) and the
 * recommended-apps screen shown right after a successful install
 * (`core/src/components/setup/RecommendedApps.vue`).
 *
 * Locators are role/label based; the two view containers have no accessible
 * name, so they keep their product-owned `data-cy` hooks.
 */
export class SetupPage {
	constructor(private readonly page: Page) {}

	/** Open the installation wizard (served at the instance root when uninstalled). */
	async open(): Promise<void> {
		await this.page.goto('/')
		await this.form().waitFor({ state: 'visible' })
	}

	form(): Locator {
		return this.page.locator('[data-cy-setup-form]')
	}

	adminLoginField(): Locator {
		return this.page.getByRole('textbox', { name: 'Administration account name' })
	}

	adminPasswordField(): Locator {
		// A password <input> has no textbox role, so address it by its label
		return this.page.getByLabel('Administration account password')
	}

	dataFolderField(): Locator {
		return this.page.getByRole('textbox', { name: 'Data folder' })
	}

	databaseRadio(name: DatabaseName): Locator {
		return this.page.getByRole('radio', { name })
	}

	submitButton(): Locator {
		return this.page.getByRole('button', { name: 'Install' })
	}

	/** Select a database engine and confirm the radio actually toggled. */
	async selectDatabase(name: DatabaseName): Promise<void> {
		const radio = this.databaseRadio(name)
		// The radio input is visually hidden inside NcCheckboxRadioSwitch, so force it
		await radio.check({ force: true })
		await expect(radio).toBeChecked()
	}

	/** Fill the connection fields for a non-SQLite database backend. */
	async fillDatabaseConnection(connection: DatabaseConnection): Promise<void> {
		await this.page.getByRole('textbox', { name: 'Database user' }).fill(connection.user)
		await this.page.getByLabel('Database password').fill(connection.password)
		await this.page.getByRole('textbox', { name: 'Database name' }).fill(connection.name)
		await this.page.getByRole('textbox', { name: 'Database host' }).fill(connection.host)
	}

	/**
	 * Fill the administration account and submit the wizard, then wait for the
	 * install to finish and land on the recommended-apps screen.
	 */
	async install(adminUser: string, adminPassword: string): Promise<void> {
		await this.adminLoginField().fill(adminUser)
		await this.adminPasswordField().fill(adminPassword)
		await this.submitButton().click()

		await this.page.waitForURL(/\/core\/apps\/recommended/)
		await this.recommendedApps().waitFor({ state: 'visible' })
	}

	// --- Recommended apps screen -------------------------------------------

	recommendedApps(): Locator {
		return this.page.locator('[data-cy-setup-recommended-apps]')
	}

	recommendedAppsHeading(): Locator {
		return this.page.getByRole('heading', { name: 'Recommended apps' })
	}

	skipButton(): Locator {
		return this.page.getByRole('button', { name: 'Skip' })
	}

	installRecommendedButton(): Locator {
		return this.page.getByRole('button', { name: 'Install recommended apps' })
	}

	/**
	 * Install the recommended apps and confirm the per-app password dialog that
	 * `@nextcloud/password-confirmation` raises for each enable request. The
	 * enables fire together, so one dialog is shown after another (never stacked):
	 * confirm each, waiting for the prompt to be consumed — the input clears for
	 * the next app, or the dialog closes after the last — before looking for the
	 * next. Confirm up to `appCount`, stopping early if a fresh session needs none.
	 */
	async installRecommendedApps(password: string, appCount: number): Promise<void> {
		await this.installRecommendedButton().click()

		for (let i = 0; i < appCount; i++) {
			const dialog = this.page.getByRole('dialog', { name: 'Authentication required' })
			try {
				await dialog.waitFor({ state: 'visible', timeout: 10_000 })
			} catch {
				break
			}

			const input = dialog.locator('input[type="password"]')
			await input.fill(password)
			await dialog.getByRole('button', { name: 'Confirm' }).click()

			// Wait until this prompt is resolved before seeking the next one, so the
			// same dialog is never confirmed twice.
			await expect(input).not.toHaveValue(password, { timeout: 10_000 }).catch(() => {})
		}
	}
}
