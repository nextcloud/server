/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import Config from '../services/ConfigService.ts'
import logger from '../services/logger.ts'

const config = new Config()
// note: some chars removed on purpose to make them human friendly when read out
const passwordSet = 'abcdefgijkmnopqrstwxyzABCDEFGHJKLMNPQRSTWXYZ23456789'

/**
 * Generate a password using server-side policy generation when available,
 * otherwise fall back to a locally generated password.
 *
 * @param verbose - If enabled the status is shown to the user via toast.
 * @returns A generated password.
 */
export default async function(verbose = false): Promise<string> {
	const generateUrl = config.passwordPolicy.api?.generate

	if (generateUrl) {
		try {
			const request = await axios.get(generateUrl)
			const password = request.data?.ocs?.data?.password

			if (password) {
				if (verbose) {
					showSuccess(t('files_sharing', 'Password created successfully'))
				}
				return password
			}
		} catch (error) {
			logger.info('Error generating password from password_policy', { error })
			if (verbose) {
				showError(t('files_sharing', 'Error generating password from password policy'))
			}
		}
	}

	return generateLocalPassword()
}

/**
 * Generate a human-friendly random password.
 *
 * Uses cryptographically secure random values when available.
 *
 * @param length - The password length.
 * @returns A random password string.
 */
function generateLocalPassword(length = 10): string {
	const bytes = new Uint8Array(length)
	fillRandomValues(bytes)

	let password = ''
	for (const byte of bytes) {
		// Scale the byte range into a valid character index.
		// Avoid `% passwordSet.length` here.
		const index = Math.floor(byte * passwordSet.length / 256)
		password += passwordSet.charAt(index)
	}

	return password
}

/**
 * Fill the given array with random bytes.
 *
 * Uses `crypto.getRandomValues()` when available and falls back to
 * `Math.random()` only as a last resort in environments without the crypto API.
 *
 * @param array - The array to fill with random values.
 */
function fillRandomValues(array: Uint8Array): void {
	if (globalThis.crypto?.getRandomValues) {
		globalThis.crypto.getRandomValues(array)
		return
	}

	// Last-resort fallback when the crypto API is unavailable.
	for (let i = 0; i < array.length; i++) {
		// NOTE: This is not cryptographically secure.
		array[i] = Math.floor(Math.random() * 256)
	}
}
