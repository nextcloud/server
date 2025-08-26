/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { InvalidFilenameError, InvalidFilenameErrorReason, validateFilename } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'

/**
 * Get the validity of a filename (empty if valid).
 * This can be used for `setCustomValidity` on input elements
 * @param name The filename
 * @param escape Escape the matched string in the error (only set when used in HTML)
 */
export function getGuestNameValidity(name: string, escape = false): string {
	if (name.trim() === '') {
		return t('files', 'Names must not be empty.')
	}

	if (name.startsWith('.')) {
		return t('files', 'Names must not start with a dot.')
	}

	try {
		validateFilename(name)
		return ''
	} catch (error) {
		if (!(error instanceof InvalidFilenameError)) {
			throw error
		}

		switch (error.reason) {
		case InvalidFilenameErrorReason.Character:
			return t('files', '"{char}" is not allowed inside a name.', { char: error.segment }, undefined, { escape })
		case InvalidFilenameErrorReason.ReservedName:
			return t('files', '"{segment}" is a reserved name and not allowed.', { segment: error.segment }, undefined, { escape: false })
		case InvalidFilenameErrorReason.Extension:
			if (error.segment.match(/\.[a-z]/i)) {
				return t('files', '"{extension}" is not an allowed name.', { extension: error.segment }, undefined, { escape: false })
			}
			return t('files', 'Names must not end with "{extension}".', { extension: error.segment }, undefined, { escape: false })
		default:
			return t('files', 'Invalid name.')
		}
	}
}
