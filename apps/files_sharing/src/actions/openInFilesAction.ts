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
import { translate as t } from '@nextcloud/l10n'
import type { Node } from '@nextcloud/files'

import { registerFileAction, FileAction, DefaultType } from '../../../files/src/services/FileAction'
import { sharesViewId, sharedWithYouViewId, sharedWithOthersViewId, sharingByLinksViewId } from '../views/shares'

export const action = new FileAction({
	id: 'open-in-files',
	displayName: () => t('files', 'Open in files'),
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
			{ dir: node.dirname, fileid: node.fileid },
		)
		return null
	},

	default: DefaultType.HIDDEN,
	// Before openFolderAction
	order: -1000,
})

registerFileAction(action)
