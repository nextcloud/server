/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { IFile } from '@nextcloud/files'

import axios from '@nextcloud/axios'
import { emit } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'
import { logger } from '../services/logger.ts'

/**
 * Rename a file in place via WebDAV.
 *
 * The Files app renames through an inline editor on the file-list row, which the
 * viewer cannot host, so we perform the same MOVE here and emit the same events
 * to keep the Files app in sync. The node is mutated (basename/source) so the
 * caller can keep the same reference.
 *
 * @param file - The file to rename
 * @param newName - The requested new basename
 * @return true if the file was renamed, false if the name was unchanged
 * @throws Error with a translated message if the name is invalid or the request fails
 */
export async function renameFile(file: IFile, newName: string): Promise<boolean> {
	const name = newName.trim()
	if (name === '' || name === file.basename) {
		return false
	}
	if (name.includes('/')) {
		throw new Error(t('viewer', 'The name must not contain "/".'))
	}

	const oldSource = file.source
	const oldEncodedSource = file.encodedSource

	// Mutates the node's basename/source in place.
	file.rename(name)

	try {
		await axios({
			method: 'MOVE',
			url: oldEncodedSource,
			headers: {
				Destination: file.encodedSource,
				Overwrite: 'F',
			},
		})
	} catch (error) {
		// Roll back the optimistic rename so the caller's node stays consistent.
		file.rename(basenameFromSource(oldSource))
		logger.error('Failed to rename file', { error, oldSource })
		throw new Error(t('viewer', 'Could not rename the file.'))
	}

	emit('files:node:updated', file)
	emit('files:node:renamed', file)
	emit('files:node:moved', { node: file, oldSource })

	return true
}

/**
 * Extract the basename from a full source URL.
 *
 * @param source - The node source URL
 */
function basenameFromSource(source: string): string {
	return decodeURIComponent(source.split('/').pop() ?? '')
}
