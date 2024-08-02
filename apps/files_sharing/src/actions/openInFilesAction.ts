/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import type { Node } from '@nextcloud/files'

import { registerFileAction, FileAction, DefaultType, FileType } from '@nextcloud/files'
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
