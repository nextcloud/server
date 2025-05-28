/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { InvalidFilenameError, InvalidFilenameErrorReason, validateFilename } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'

/**
 * Get the validity of a filename (empty if valid).
 * This can be used for `setCustomValidity` on input elements
 * @param input The filename
 * @param escape Escape the matched string in the error (only set when used in HTML)
 */
export function getFilenameValidity(input: string, escape = false): string {
	if (input.trim() === '') {
		return t('files', 'This field must not be empty.')
	}

	try {
		validateFilename(input)
		return ''
	} catch (error) {
		if (!(error instanceof InvalidFilenameError)) {
			throw error
		}

		switch (error.reason) {
		case InvalidFilenameErrorReason.Character:
			return t('files', 'The character "{char}" is not allowed.', { char: error.segment }, undefined, { escape })
		case InvalidFilenameErrorReason.ReservedName:
			return t('files', '"{segment}" is reserved and cannot be used.', { segment: error.segment }, undefined, { escape: false })
		case InvalidFilenameErrorReason.Extension:
			if (error.segment.match(/\.[a-z]/i)) {
				return t('files', '"{extension}" is not a supported type.', { extension: error.segment }, undefined, { escape: false })
			}
			return t('files', 'Cannot end with "{extension}".', { extension: error.segment }, undefined, { escape: false })
		default:
			return t('files', 'This value is not allowed.')
		}
	}
}
