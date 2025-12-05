/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { DefaultType, FileAction, FileType } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { systemTagsViewId } from '../files_views/systemtagsView.ts'

export const action = new FileAction({
	id: 'systemtags:open-in-files',
	displayName: () => t('systemtags', 'Open in Files'),
	iconSvgInline: () => '',

	enabled({ nodes, view }) {
		// Only for the system tags view
		if (view.id !== systemTagsViewId) {
			return false
		}
		// Only for single nodes
		if (nodes.length !== 1 || !nodes[0]) {
			return false
		}
		// Do not open tags (keep the default action) and only open folders
		return nodes[0].attributes['is-tag'] !== true
			&& nodes[0].type === FileType.Folder
	},

	async exec({ nodes }) {
		if (!nodes[0] || nodes.length !== 1) {
			return false
		}

		let dir = nodes[0].dirname
		if (nodes[0].type === FileType.Folder) {
			dir = nodes[0].path
		}

		window.OCP.Files.Router.goToRoute(
			null, // use default route
			{ view: 'files', fileid: String(nodes[0].fileid) },
			{ dir, openfile: 'true' },
		)
		return null
	},

	// Before openFolderAction
	order: -1000,
	default: DefaultType.HIDDEN,
})
