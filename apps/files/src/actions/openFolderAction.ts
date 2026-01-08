/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import FolderSvg from '@mdi/svg/svg/folder.svg?raw'
import { DefaultType, FileAction, FileType, Permission } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'

export const action = new FileAction({
	id: 'open-folder',
	displayName({ nodes }) {
		if (nodes.length !== 1 || !nodes[0]) {
			return t('files', 'Open folder')
		}

		// Only works on single node
		const displayName = nodes[0].displayname
		return t('files', 'Open folder {displayName}', { displayName })
	},
	iconSvgInline: () => FolderSvg,

	enabled({ nodes }) {
		// Only works on single node
		if (nodes.length !== 1 || !nodes[0]) {
			return false
		}

		const node = nodes[0]
		if (!node.isDavResource) {
			return false
		}

		return node.type === FileType.Folder
			&& (node.permissions & Permission.READ) !== 0
	},

	async exec({ nodes, view }) {
		const node = nodes[0]
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
