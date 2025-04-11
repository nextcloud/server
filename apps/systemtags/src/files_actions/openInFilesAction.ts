/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { type Node } from '@nextcloud/files'

import { FileType, FileAction, DefaultType } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'

import { systemTagsViewId } from '../files_views/systemtagsView'

export const action = new FileAction({
	id: 'systemtags:open-in-files',
	displayName: () => t('systemtags', 'Open in Files'),
	iconSvgInline: () => '',

	enabled(nodes, view) {
		// Only for the system tags view
		if (view.id !== systemTagsViewId) {
			return false
		}
		// Only for single nodes
		if (nodes.length !== 1) {
			return false
		}
		// Do not open tags (keep the default action) and only open folders
		return nodes[0].attributes['is-tag'] !== true
			&& nodes[0].type === FileType.Folder
	},

	async exec(node: Node) {
		let dir = node.dirname
		if (node.type === FileType.Folder) {
			dir = node.path
		}

		window.OCP.Files.Router.goToRoute(
			null, // use default route
			{ view: 'files', fileid: String(node.fileid) },
			{ dir, openfile: 'true' },
		)
		return null
	},

	// Before openFolderAction
	order: -1000,
	default: DefaultType.HIDDEN,
})
