/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { expect, test } from '../../support/fixtures/encryption-personal-settings-page.ts'
import {
	desyncPrivateKeyPassword,
	disableEncryption,
	enablePerAccountKeyEncryption,
	enableRecoveryKey,
	setPasswordRecoveryForUser,
} from '../../support/utils/encryption.ts'
import { getToast } from '../../support/utils/toasts.ts'
import { loginAs } from '../../support/utils/users.ts'

const RECOVERY_KEY_PASSWORD = 'recovery-key-password'

/**
 * The personal settings of the default encryption module.
 *
 * Server-side encryption is instance-wide state, so the file is named
 * `admin-settings-*`: that is the pattern `playwright.config.ts` runs in its own
 * serial project.
 */
test.describe('encryption: personal settings', () => {
	// The accounts of all tests are created while encryption is enabled, so that
	// they have encryption keys of their own.
	test.beforeAll(enablePerAccountKeyEncryption)
	test.afterAll(disableEncryption)

	test.describe('outdated private key password', () => {
		/** The password the private key of the current account is encrypted with. */
		let privateKeyPassword: string

		test.beforeEach(async ({ page, user, encryptionSettings }) => {
			privateKeyPassword = await desyncPrivateKeyPassword(user)
			await loginAs(page, user)
			await encryptionSettings.open()
		})

		test('offers to update the private key password', async ({ encryptionSettings }) => {
			await expect(encryptionSettings.updatePrivateKeyPasswordForm()).toBeVisible()
			await expect(encryptionSettings.oldLoginPasswordField()).toBeVisible()
			await expect(encryptionSettings.currentLoginPasswordField()).toBeVisible()
			await expect(encryptionSettings.updateButton()).toBeVisible()
			// Without password recovery there is no point in mentioning the administrator
			await expect(encryptionSettings.recoveryHint()).toHaveCount(0)
		})

		test('points to the administrator if password recovery is enabled', async ({ user, encryptionSettings }) => {
			await setPasswordRecoveryForUser(user.userId, true)
			await encryptionSettings.open()

			await expect(encryptionSettings.recoveryHint())
				.toHaveText(/ask your administrator to recover your files/)
		})

		test('rejects a wrong current log-in password', async ({ page, encryptionSettings }) => {
			const response = await encryptionSettings.updatePrivateKeyPassword(privateKeyPassword, 'not-the-log-in-password')

			// Complaining about the current password means the old one was submitted, too
			expect(response.status()).toBe(400)
			await expect(getToast(page, 'The current log-in password was not correct, please try again.')).toBeVisible()

			// The form keeps its values so that only the wrong password has to be fixed
			await expect(encryptionSettings.updatePrivateKeyPasswordForm()).toBeVisible()
			await expect(encryptionSettings.oldLoginPasswordField()).toHaveValue(privateKeyPassword)
			await expect(encryptionSettings.currentLoginPasswordField()).toHaveValue('not-the-log-in-password')
		})

		test('rejects a wrong old log-in password', async ({ page, user, encryptionSettings }) => {
			const response = await encryptionSettings.updatePrivateKeyPassword('not-the-old-password', user.password)

			// Complaining about the old password means the current one was accepted
			expect(response.status()).toBe(400)
			await expect(getToast(page, 'The old password was not correct, please try again.')).toBeVisible()
			await expect(encryptionSettings.updatePrivateKeyPasswordForm()).toBeVisible()
		})

		test('updates the private key password', async ({ page, user, encryptionSettings }) => {
			const response = await encryptionSettings.updatePrivateKeyPassword(privateKeyPassword, user.password)

			expect(response.status()).toBe(200)
			// The settings reload the status, which is now successful
			await expect(getToast(page, 'Encryption app is enabled and ready')).toBeVisible()
			await expect(encryptionSettings.updatePrivateKeyPasswordForm()).toHaveCount(0)

			// The private key is really encrypted with the log-in password now: a new
			// session can unlock it, so there is nothing left to configure
			await page.context().clearCookies()
			await loginAs(page, user)
			await encryptionSettings.open()
			await expect(encryptionSettings.section()).toHaveCount(0)
		})
	})

	test.describe('uninitialized keys', () => {
		test.beforeEach(async ({ page, user, encryptionSettings }) => {
			// Log in while encryption is switched off: the account keys are never
			// unlocked for that session, the state accounts are in when encryption is
			// enabled while they are logged in.
			await runOcc(['encryption:disable'])
			await loginAs(page, user)
			await runOcc(['encryption:enable'])

			await encryptionSettings.open()
		})

		test('asks to log in again', async ({ encryptionSettings }) => {
			await expect(encryptionSettings.keysNotInitializedWarning())
				.toHaveText(/please log-out and log-in again/)
			await expect(encryptionSettings.updatePrivateKeyPasswordForm()).toHaveCount(0)
		})
	})

	test.describe('password recovery', () => {
		test.beforeEach(async ({ page, user, adminRequest, encryptionSettings }) => {
			await loginAs(page, user)
			await enableRecoveryKey(adminRequest, RECOVERY_KEY_PASSWORD)
			await encryptionSettings.open()
		})

		// Leave the instance without a recovery key, otherwise the encryption section
		// keeps being rendered for accounts that have nothing else to configure
		test.afterAll(async () => {
			await runOcc(['config:app:delete', 'encryption', 'recoveryAdminEnabled'])
		})

		test('can be enabled and disabled', async ({ encryptionSettings }) => {
			await expect(encryptionSettings.passwordRecoverySwitch()).not.toBeChecked()

			await encryptionSettings.setPasswordRecovery(true)
			await encryptionSettings.open()
			await expect(encryptionSettings.passwordRecoverySwitch()).toBeChecked()

			await encryptionSettings.setPasswordRecovery(false)
			await encryptionSettings.open()
			await expect(encryptionSettings.passwordRecoverySwitch()).not.toBeChecked()
		})
	})
})
