/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { translate as t } from '@nextcloud/l10n'
import { type Node, FileType, FileAction, DefaultType } from '@nextcloud/files'

/**
 * TODO: Move away from a redirect and handle
 * navigation straight out of the recent view
 */
export const action = new FileAction({
	id: 'open-in-files-recent',
	displayName: () => t('files', 'Open in Files'),
	iconSvgInline: () => '',

	enabled: (nodes, view) => view.id === 'recent',

	async exec(node: Node) {
		let dir = node.dirname
		if (node.type === FileType.Folder) {
			dir = dir + '/' + node.basename
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
