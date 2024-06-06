/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Node } from '@nextcloud/files'

import { registerFileAction, FileAction, DefaultType } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'

import { sharesViewId, sharedWithYouViewId, sharedWithOthersViewId, sharingByLinksViewId } from '../views/shares'

export const action = new FileAction({
	id: 'open-in-files',
	displayName: () => t('files', 'Open in Files'),
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
		window.OCP.Files.Router.goToRoute(
			null, // use default route
			{ view: 'files', fileid: node.fileid },
			{ dir: node.dirname },
		)
		return null
	},

	// Before openFolderAction
	order: -1000,
	default: DefaultType.HIDDEN,
})

registerFileAction(action)
