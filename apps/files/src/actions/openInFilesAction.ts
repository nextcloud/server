/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { DefaultType, FileAction, FileType } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { VIEW_ID as SEARCH_VIEW_ID } from '../views/search.ts'

export const action = new FileAction({
	id: 'open-in-files',
	displayName: () => t('files', 'Open in Files'),
	iconSvgInline: () => '',

	enabled({ view }) {
		return view.id === 'recent' || view.id === SEARCH_VIEW_ID
	},

	async exec({ nodes }) {
		if (!nodes[0]) {
			return false
		}

		let dir = nodes[0].dirname
		if (nodes[0].type === FileType.Folder) {
			dir = dir + '/' + nodes[0].basename
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
