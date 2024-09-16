/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { Permission, Node, FileType, View, FileAction, DefaultType } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import FolderSvg from '@mdi/svg/svg/folder.svg?raw'

export const action = new FileAction({
	id: 'open-folder',
	displayName(files: Node[]) {
		// Only works on single node
		const displayName = files[0].displayname
		return t('files', 'Open folder {displayName}', { displayName })
	},
	iconSvgInline: () => FolderSvg,

	enabled(nodes: Node[]) {
		// Only works on single node
		if (nodes.length !== 1) {
			return false
		}

		const node = nodes[0]

		if (!node.isDavRessource) {
			return false
		}

		return node.type === FileType.Folder
			&& (node.permissions & Permission.READ) !== 0
	},

	async exec(node: Node, view: View) {
		if (!node || node.type !== FileType.Folder) {
			return false
		}

		window.OCP.Files.Router.goToRoute(
			null,
			{ view: view.id, fileid: String(node.fileid) },
			{ dir: node.path },
		)
		return null
	},

	// Main action if enabled, meaning folders only
	default: DefaultType.HIDDEN,
	order: -100,
})
