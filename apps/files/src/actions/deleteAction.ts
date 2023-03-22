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
import { registerFileAction, Permission, FileAction } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'
import TrashCan from '@mdi/svg/svg/trash-can.svg?raw'

registerFileAction(new FileAction({
	id: 'delete',
	displayName(nodes, view) {
		return view.id === 'trashbin'
			? t('files_trashbin', 'Delete permanently')
			: t('files', 'Delete')
	},
	iconSvgInline: () => TrashCan,
	enabled(nodes) {
		return nodes.length > 0 && nodes
			.map(node => node.permissions)
			.every(permission => (permission & Permission.DELETE) !== 0)
	},
	async exec(node) {
		try {
			await axios.delete(node.source)
			return true
		} catch (error) {
			console.error(error)
			return false
		}
	},
}))
