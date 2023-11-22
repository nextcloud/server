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
import { join } from 'path'
import { Permission, Node, FileType, View, FileAction, DefaultType } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import FolderSvg from '@mdi/svg/svg/folder.svg?raw'

export const action = new FileAction({
	id: 'open-folder',
	displayName(files: Node[]) {
		// Only works on single node
		const displayName = files[0].attributes.displayName || files[0].basename
		return t('files', 'Open folder {displayName}', { displayName })
	},
	iconSvgInline: () => FolderSvg,

	enabled(nodes: Node[]) {
		// Only works on single node
		if (nodes.length !== 1) {
			return false
		}

		const node = nodes[0]

		if (!node.isDavRessource) {
			return false
		}

		return node.type === FileType.Folder
			&& (node.permissions & Permission.READ) !== 0
	},

	async exec(node: Node, view: View, dir: string) {
		if (!node || node.type !== FileType.Folder) {
			return false
		}

		window.OCP.Files.Router.goToRoute(
			null,
			{ view: view.id, fileid: node.fileid },
			{ dir: join(dir, node.basename) },
		)
		return null
	},

	// Main action if enabled, meaning folders only
	default: DefaultType.HIDDEN,
	order: -100,
})
