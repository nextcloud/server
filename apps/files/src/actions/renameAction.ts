/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import PencilSvg from '@mdi/svg/svg/pencil-outline.svg?raw'
import { emit } from '@nextcloud/event-bus'
import { FileAction, Permission } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { dirname } from 'path'
import { useFilesStore } from '../store/files.ts'
import { getPinia } from '../store/index.ts'

export const ACTION_RENAME = 'rename'

export const action = new FileAction({
	id: ACTION_RENAME,
	displayName: () => t('files', 'Rename'),
	iconSvgInline: () => PencilSvg,

	enabled: ({ nodes, view }) => {
		if (nodes.length === 0 || !nodes[0]) {
			return false
		}

		// Disable for single file shares
		if (view.id === 'public-file-share') {
			return false
		}

		const node = nodes[0]
		const filesStore = useFilesStore(getPinia())
		const parentNode = node.dirname === '/'
			? filesStore.getRoot(view.id)
			: filesStore.getNode(dirname(node.source))
		const parentPermissions = parentNode?.permissions || Permission.NONE

		// Enable if the node has update permissions or the node
		// has delete permission and the parent folder allows creating files
		return (
			(
				Boolean(node.permissions & Permission.DELETE)
				&& Boolean(parentPermissions & Permission.CREATE)
			)
			|| Boolean(node.permissions & Permission.UPDATE)
		)
	},

	async exec({ nodes }) {
		// Renaming is a built-in feature of the files app
		emit('files:node:rename', nodes[0])
		return null
	},

	order: 10,

	hotkey: {
		description: t('files', 'Rename'),
		key: 'F2',
	},
})
