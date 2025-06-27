/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Node } from '@nextcloud/files'

import { t } from '@nextcloud/l10n'
import { FileType, FileAction, DefaultType } from '@nextcloud/files'
import { VIEW_ID as SEARCH_VIEW_ID } from '../views/search'

export const action = new FileAction({
	id: 'open-in-files',
	displayName: () => t('files', 'Open in Files'),
	iconSvgInline: () => '',

	enabled: (nodes, view: View) => [
		'home',
		'recent',
		SEARCH_VIEW_ID,
	].includes(view.id),

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
