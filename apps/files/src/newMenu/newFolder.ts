/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { NewMenuEntry, Node } from '@nextcloud/files'

import FolderPlusSvg from '@mdi/svg/svg/folder-plus-outline.svg?raw'
import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { Folder, Permission } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { basename } from 'path'
import logger from '../logger.ts'
import { newNodeName } from '../utils/newNodeDialog.ts'

type createFolderResponse = {
	fileid: number
	source: string
}

/**
 *
 * @param root
 * @param name
 */
async function createNewFolder(root: Folder, name: string): Promise<createFolderResponse> {
	const source = root.source + '/' + name
	const encodedSource = root.encodedSource + '/' + encodeURIComponent(name)

	const response = await axios({
		method: 'MKCOL',
		url: encodedSource,
		headers: {
			Overwrite: 'F',
		},
	})
	return {
		fileid: parseInt(response.headers['oc-fileid']),
		source,
	}
}

export const entry: NewMenuEntry = {
	id: 'newFolder',
	displayName: t('files', 'New folder'),
	enabled: (context: Folder) => Boolean(context.permissions & Permission.CREATE) && Boolean(context.permissions & Permission.READ),

	// Make the svg icon color match the primary element color
	iconSvgInline: FolderPlusSvg.replace(/viewBox/gi, 'style="color: var(--color-primary-element)" viewBox'),
	order: 0,

	async handler(context: Folder, content: Node[]) {
		const name = await newNodeName(t('files', 'New folder'), content)
		if (name === null) {
			return
		}
		try {
			const { fileid, source } = await createNewFolder(context, name.trim())

			// Create the folder in the store
			const folder = new Folder({
				source,
				id: fileid,
				mtime: new Date(),
				owner: context.owner,
				permissions: Permission.ALL,
				root: context?.root || '/files/' + getCurrentUser()?.uid,
				// Include mount-type from parent folder as this is inherited
				attributes: {
					'mount-type': context.attributes?.['mount-type'],
					'owner-id': context.attributes?.['owner-id'],
					'owner-display-name': context.attributes?.['owner-display-name'],
				},
			})

			// Show success
			emit('files:node:created', folder)
			showSuccess(t('files', 'Created new folder "{name}"', { name: basename(source) }))
			logger.debug('Created new folder', { folder, source })

			// Navigate to the new folder
			window.OCP.Files.Router.goToRoute(
				null, // use default route
				{ view: 'files', fileid: String(fileid) },
				{ dir: context.path },
			)
		} catch (error) {
			logger.error('Creating new folder failed', { error })
			showError('Creating new folder failed')
		}
	},
}
