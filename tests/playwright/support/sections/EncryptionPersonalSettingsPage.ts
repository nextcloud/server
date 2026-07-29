/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page, Response } from '@playwright/test'

/**
 * The "Basic encryption module" section of the personal security settings,
 * provided by the encryption app.
 */
export class EncryptionPersonalSettingsPage {
	constructor(private readonly page: Page) {}

	/**
	 * The section container. It is only rendered when the encryption app has
	 * something for the account to do, so `toHaveCount(0)` asserts "nothing to do".
	 */
	section(): Locator {
		// The container has no accessible name, so the app's mount point is used
		return this.page.locator('#encryption-settings-section')
	}

	/** Warning shown when the account keys were never unlocked for this session. */
	keysNotInitializedWarning(): Locator {
		return this.section().getByRole('note').filter({ hasText: 'keys are not initialized' })
	}

	/** The "Update private key password" form, rendered as a labelled group. */
	updatePrivateKeyPasswordForm(): Locator {
		return this.page.getByRole('group', { name: 'Update private key password' })
	}

	oldLoginPasswordField(): Locator {
		return this.updatePrivateKeyPasswordForm().getByLabel('Old log-in password')
	}

	currentLoginPasswordField(): Locator {
		return this.updatePrivateKeyPasswordForm().getByLabel('Current log-in password')
	}

	updateButton(): Locator {
		return this.updatePrivateKeyPasswordForm().getByRole('button', { name: 'Update' })
	}

	/** Hint to ask an administrator, only shown if password recovery is enabled. */
	recoveryHint(): Locator {
		return this.updatePrivateKeyPasswordForm().getByRole('note')
	}

	passwordRecoverySwitch(): Locator {
		return this.section().getByRole('checkbox', { name: 'Enable password recovery' })
	}

	/**
	 * Open the personal security settings.
	 *
	 * Waits for a section of the page that is always there, so that assertions on
	 * the absence of the encryption section cannot pass before the page rendered.
	 */
	async open(): Promise<void> {
		await this.page.goto('settings/user/security')
		await this.page.getByRole('heading', { name: 'Devices & sessions' }).waitFor()
	}

	/**
	 * Fill in and submit the "Update private key password" form.
	 *
	 * @param oldPassword - Value for "Old log-in password"
	 * @param currentPassword - Value for "Current log-in password"
	 * @return The response of the update request
	 */
	async updatePrivateKeyPassword(oldPassword: string, currentPassword: string): Promise<Response> {
		await this.oldLoginPasswordField().fill(oldPassword)
		await this.currentLoginPasswordField().fill(currentPassword)

		// Registered before the click, otherwise the response can be missed
		const updated = this.page.waitForResponse((response) => response.url().includes('/apps/encryption/ajax/updatePrivateKeyPassword'))
		await this.updateButton().click()
		return await updated
	}

	/**
	 * Toggle password recovery and wait for the change to be stored.
	 *
	 * The switch is debounced, so the request starts about a second after the
	 * click; the real input is visually hidden and needs a forced action.
	 *
	 * @param enabled - Whether password recovery should be enabled
	 */
	async setPasswordRecovery(enabled: boolean): Promise<void> {
		const saved = this.page.waitForResponse((response) => response.url().includes('/apps/encryption/ajax/userSetRecovery')
			&& response.request().method() === 'POST')
		await this.passwordRecoverySwitch().setChecked(enabled, { force: true })
		await saved
	}
}
