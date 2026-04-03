/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { emit } from '@nextcloud/event-bus'
import { Permission, type Node, FileAction, View } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import PencilSvg from '@mdi/svg/svg/pencil-outline.svg?raw'
import { getPinia } from '../store'
import { useFilesStore } from '../store/files'
import { dirname } from 'path'

export const ACTION_RENAME = 'rename'

export const action = new FileAction({
	id: ACTION_RENAME,
	displayName: () => t('files', 'Rename'),
	iconSvgInline: () => PencilSvg,

	enabled: (nodes: Node[], view: View) => {
		if (nodes.length === 0) {
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

	async exec(node: Node) {
		// Renaming is a built-in feature of the files app
		emit('files:node:rename', node)
		return null
	},

	order: 10,

	hotkey: {
		description: t('files', 'Rename'),
		key: 'F2',
	},
})
