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
// Character sets for password generation
const CHARS_LOWER = 'abcdefgijkmnopqrstwxyz'
const CHARS_UPPER = 'ABCDEFGHJKLMNPQRSTWXYZ'
const CHARS_DIGITS = '23456789'
const CHARS_SPECIAL = '!@#$%^&*'
const CHARS_HUMAN_READABLE = CHARS_LOWER + CHARS_UPPER + CHARS_DIGITS

/**
 * Generate a valid policy password or request a valid password if password_policy is enabled
 *
 * @param verbose If enabled the the status is shown to the user via toast
 */
export default async function(verbose = false): Promise<string> {
	// password policy is enabled, let's request a pass
	if (config.passwordPolicy.api && config.passwordPolicy.api.generate) {
		try {
			const request = await axios.get(config.passwordPolicy.api.generate, {
				params: { context: 'sharing' },
			})
			if (request.data.ocs.data.password) {
				if (verbose) {
					showSuccess(t('files_sharing', 'Password created successfully'))
				}
				return request.data.ocs.data.password
			}
		} catch (error) {
			logger.info('Error generating password from password_policy', { error })
			if (verbose) {
				showError(t('files_sharing', 'Error generating password from password policy'))
			}
		}
	}

	// Fallback: generate password based on sharing policy from capabilities
	const sharingPolicy = config.passwordPolicy?.policies?.sharing
	const minLength = Math.max(sharingPolicy?.minLength ?? config.passwordPolicy?.minLength ?? 10, 8)
	const needsSpecialChars = sharingPolicy?.enforceSpecialCharacters ?? config.passwordPolicy?.enforceSpecialCharacters ?? false
	const needsUpperLower = sharingPolicy?.enforceUpperLowerCase ?? config.passwordPolicy?.enforceUpperLowerCase ?? false
	const needsNumeric = sharingPolicy?.enforceNumericCharacters ?? config.passwordPolicy?.enforceNumericCharacters ?? false

	let password = ''
	let chars = CHARS_HUMAN_READABLE

	// Add required character types
	if (needsUpperLower) {
		password += getRandomChar(CHARS_UPPER)
		password += getRandomChar(CHARS_LOWER)
	}
	if (needsNumeric) {
		password += getRandomChar(CHARS_DIGITS)
	}
	if (needsSpecialChars) {
		password += getRandomChar(CHARS_SPECIAL)
		chars += CHARS_SPECIAL
	}

	// Fill remaining length
	const remainingLength = Math.max(minLength - password.length, 0)
	const array = new Uint8Array(remainingLength)
	getRandomValues(array)
	for (let i = 0; i < array.length; i++) {
		password += chars.charAt(Math.floor(array[i] / 256 * chars.length))
	}

	// Shuffle to randomize character positions
	return shuffleString(password)
}

/**
 * Fills the given array with cryptographically secure random values.
 * If the crypto API is not available, it falls back to less secure Math.random().
 * Crypto API is available in modern browsers on secure contexts (HTTPS).
 *
 * @param array - The array to fill with random values.
 */
function getRandomValues(array: Uint8Array): void {
	if (self?.crypto?.getRandomValues) {
		self.crypto.getRandomValues(array)
		return
	}

	let len = array.length
	while (len--) {
		array[len] = Math.floor(Math.random() * 256)
	}
}

/**
 * Get a random character from the given character set.
 *
 * @param chars - The character set to choose from.
 */
function getRandomChar(chars: string): string {
	const array = new Uint8Array(1)
	getRandomValues(array)
	return chars.charAt(Math.floor(array[0] / 256 * chars.length))
}

/**
 * Shuffle a string randomly using Fisher-Yates algorithm.
 *
 * @param str - The string to shuffle.
 */
function shuffleString(str: string): string {
	const arr = str.split('')
	for (let i = arr.length - 1; i > 0; i--) {
		const array = new Uint8Array(1)
		getRandomValues(array)
		const j = Math.floor(array[0] / 256 * (i + 1))
		const temp = arr[i]
		arr[i] = arr[j]
		arr[j] = temp
	}
	return arr.join('')
}
