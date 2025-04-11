/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { Node, FileType, Permission, View, FileAction } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import FolderMoveSvg from '@mdi/svg/svg/folder-move.svg?raw'
import { isPublicShare } from '@nextcloud/sharing/public'

export const action = new FileAction({
	id: 'view-in-folder',
	displayName() {
		return t('files', 'View in folder')
	},
	iconSvgInline: () => FolderMoveSvg,

	enabled(nodes: Node[], view: View) {
		// Not enabled for public shares
		if (isPublicShare()) {
			return false
		}

		// Only works outside of the main files view
		if (view.id === 'files') {
			return false
		}

		// Only works on single node
		if (nodes.length !== 1) {
			return false
		}

		const node = nodes[0]

		if (!node.isDavRessource) {
			return false
		}

		// Can only view files that are in the user root folder
		if (!node.root?.startsWith('/files')) {
			return false
		}

		if (node.permissions === Permission.NONE) {
			return false
		}

		return node.type === FileType.File
	},

	async exec(node: Node) {
		if (!node || node.type !== FileType.File) {
			return false
		}

		window.OCP.Files.Router.goToRoute(
			null,
			{ view: 'files', fileid: String(node.fileid) },
			{ dir: node.dirname },
		)
		return null
	},

	order: 80,
})
