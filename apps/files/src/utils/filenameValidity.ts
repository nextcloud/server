/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { InvalidFilenameError, InvalidFilenameErrorReason, validateFilename } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'

/**
 * Get the validity of a filename (empty if valid).
 * This can be used for `setCustomValidity` on input elements
 *
 * @param name The filename
 * @param escape Escape the matched string in the error (only set when used in HTML)
 * @param isFolder Whether the filename is for a folder
 */
export function getFilenameValidity(name: string, escape = false, isFolder = false): string {
	if (name.trim() === '') {
		if (isFolder) {
			return t('files', 'Folder name must not be empty.')
		}
		return t('files', 'Filename must not be empty.')
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
				if (isFolder) {
					return t('files', '"{char}" is not allowed inside a folder name.', { char: error.segment }, { escape })
				}
				return t('files', '"{char}" is not allowed inside a filename.', { char: error.segment }, { escape })
			case InvalidFilenameErrorReason.ReservedName:
				if (isFolder) {
					return t('files', '"{segment}" is a reserved name and not allowed for folder names.', { segment: error.segment }, { escape: false })
				}
				return t('files', '"{segment}" is a reserved name and not allowed for filenames.', { segment: error.segment }, { escape: false })
			case InvalidFilenameErrorReason.Extension:
				if (!isFolder && error.segment.match(/\.[a-z]/i)) {
					return t('files', '"{extension}" is not an allowed filetype.', { extension: error.segment }, { escape: false })
				}
				if (isFolder) {
					return t('files', 'Folder names must not end with "{extension}".', { extension: error.segment }, { escape: false })
				}
				return t('files', 'Filenames must not end with "{extension}".', { extension: error.segment }, { escape: false })
			default:
				if (isFolder) {
					return t('files', 'Invalid folder name.')
				}
				return t('files', 'Invalid filename.')
		}
	}
}
