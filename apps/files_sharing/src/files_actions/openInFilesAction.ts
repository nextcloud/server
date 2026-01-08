/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { DefaultType, FileAction, FileType, registerFileAction } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { sharedWithOthersViewId, sharedWithYouViewId, sharesViewId, sharingByLinksViewId } from '../files_views/shares.ts'

export const action = new FileAction({
	id: 'files_sharing:open-in-files',
	displayName: () => t('files_sharing', 'Open in Files'),
	iconSvgInline: () => '',

	enabled: ({ view }) => [
		sharesViewId,
		sharedWithYouViewId,
		sharedWithOthersViewId,
		sharingByLinksViewId,
		// Deleted and pending shares are not
		// accessible in the files app.
	].includes(view.id),

	async exec({ nodes }) {
		const isFolder = nodes[0].type === FileType.Folder

		window.OCP.Files.Router.goToRoute(
			null, // use default route
			{
				view: 'files',
				fileid: String(nodes[0].fileid),
			},
			{
				// If this node is a folder open the folder in files
				dir: isFolder ? nodes[0].path : nodes[0].dirname,
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
