/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Node } from '@nextcloud/files'

import { registerFileAction, FileAction, DefaultType, FileType } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'

import { sharesViewId, sharedWithYouViewId, sharedWithOthersViewId, sharingByLinksViewId } from '../files_views/shares'

export const action = new FileAction({
	id: 'open-in-files',
	displayName: () => t('files_sharing', 'Open in Files'),
	iconSvgInline: () => '',

	enabled: (nodes, view) => [
		sharesViewId,
		sharedWithYouViewId,
		sharedWithOthersViewId,
		sharingByLinksViewId,
		// Deleted and pending shares are not
		// accessible in the files app.
	].includes(view.id),

	async exec(node: Node) {
		const isFolder = node.type === FileType.Folder

		window.OCP.Files.Router.goToRoute(
			null, // use default route
			{
				view: 'files',
				fileid: String(node.fileid),
			},
			{
				// If this node is a folder open the folder in files
				dir: isFolder ? node.path : node.dirname,
				// otherwise if this is a file, we should open it
				openfile: isFolder ? undefined : 'true',
			},
		)
		return null
	},

	// Before openFolderAction
	order: -1000,
	default: DefaultType.HIDDEN,
})

registerFileAction(action)
