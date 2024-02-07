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
import { Node, FileType, Permission, View, FileAction } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import FolderMoveSvg from '@mdi/svg/svg/folder-move.svg?raw'

export const action = new FileAction({
	id: 'view-in-folder',
	displayName() {
		return t('files', 'View in folder')
	},
	iconSvgInline: () => FolderMoveSvg,

	enabled(nodes: Node[], view: View) {
		// Only works outside of the main files view
		if (view.id === 'files') {
			return false
		}

		// Only works on single node
		if (nodes.length !== 1) {
			return false
		}

		const node = nodes[0]

		if (!node.isDavRessource) {
			return false
		}

		if (node.permissions === Permission.NONE) {
			return false
		}

		return node.type === FileType.File
	},

	async exec(node: Node) {
		if (!node || node.type !== FileType.File) {
			return false
		}

		window.OCP.Files.Router.goToRoute(
			null,
			{ view: 'files', fileid: node.fileid },
			{ dir: node.dirname },
		)
		return null
	},

	order: 80,
})
