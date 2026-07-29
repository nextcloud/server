/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { APIRequestContext } from '@playwright/test'

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { expect } from '@playwright/test'

/** Log-in password set by {@link desyncPrivateKeyPassword}. */
export const UPDATED_LOGIN_PASSWORD = 'updated-log-in-password'

/**
 * Turn on server-side encryption with per-account keys.
 *
 * Only without the master key does an account have a private key password of its
 * own, which is what the personal encryption settings manage. Home storages are
 * left unencrypted: the settings do not depend on it, and no test data has to be
 * decrypted again when encryption is switched off in {@link disableEncryption}.
 */
export async function enablePerAccountKeyEncryption(): Promise<void> {
	await runOcc(['app:enable', 'encryption'])
	await runOcc(['config:app:set', 'encryption', 'useMasterKey', '--value', '0', '--type', 'boolean'])
	await runOcc(['config:app:set', 'encryption', 'encryptHomeStorage', '--value', '0', '--type', 'boolean'])
	await runOcc(['encryption:enable'])
}

/**
 * Switch server-side encryption off again and restore the default configuration.
 */
export async function disableEncryption(): Promise<void> {
	await runOcc(['encryption:disable'])
	await runOcc(['config:app:delete', 'encryption', 'recoveryAdminEnabled'])
	await runOcc(['config:app:delete', 'encryption', 'encryptHomeStorage'])
	await runOcc(['config:app:delete', 'encryption', 'useMasterKey'])
	await runOcc(['app:disable', 'encryption'])
}

/**
 * Make an account's private key password differ from its log-in password — the
 * state the "Update private key password" form exists for. This happens in real
 * life when the password is changed in an external user backend.
 *
 * The account keys must already exist, meaning the account must have been created
 * while encryption was enabled. The encryption app only re-encrypts the private
 * key while encryption is enabled, so the log-in password is changed with
 * encryption switched off and `user.password` is updated to the new one.
 *
 * The session must be created *after* this call, otherwise the encryption app
 * already unlocked the private key for it.
 *
 * @param user - Account to desync — its `password` is updated in place
 * @return The password the private key is still encrypted with
 */
export async function desyncPrivateKeyPassword(user: User): Promise<string> {
	const privateKeyPassword = user.password

	await runOcc(['encryption:disable'])
	await runOcc(['user:resetpassword', user.userId, '--password-from-env'], {
		env: [`OC_PASS=${UPDATED_LOGIN_PASSWORD}`],
	})
	await runOcc(['encryption:enable'])

	user.password = UPDATED_LOGIN_PASSWORD
	return privateKeyPassword
}

/**
 * Enable the recovery key, which is what makes the "Enable password recovery"
 * setting available to accounts. The key is created on the first call, later
 * calls have to pass the same password again.
 *
 * @param request - Request context authenticated as an administrator
 * @param recoveryPassword - Password protecting the recovery key
 */
export async function enableRecoveryKey(request: APIRequestContext, recoveryPassword: string): Promise<void> {
	const response = await request.post('./apps/encryption/ajax/adminRecovery', {
		// Marks the request as an API request, so no CSRF token is required
		headers: { 'OCS-APIRequest': 'true' },
		data: {
			recoveryPassword,
			confirmPassword: recoveryPassword,
			adminEnableRecovery: true,
		},
	})
	expect(response.status(), await response.text()).toBe(200)
}

/**
 * Set the account's own "Enable password recovery" preference.
 *
 * @param userId - The account to change the preference for
 * @param enabled - Whether password recovery should be enabled
 */
export async function setPasswordRecoveryForUser(userId: string, enabled: boolean): Promise<void> {
	await runOcc(['user:setting', userId, 'encryption', 'recoveryEnabled', enabled ? '1' : '0'])
}
