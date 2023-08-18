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
import { generateUrl } from '@nextcloud/router'
import { registerFileAction, FileAction, Permission, Node, FileType, View } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import ArrowDownSvg from '@mdi/svg/svg/arrow-down.svg?raw'

const triggerDownload = function(url: string) {
	const hiddenElement = document.createElement('a')
	hiddenElement.download = ''
	hiddenElement.href = url
	hiddenElement.click()
}

const downloadNodes = function(dir: string, nodes: Node[]) {
	const secret = Math.random().toString(36).substring(2)
	const url = generateUrl('/apps/files/ajax/download.php?dir={dir}&files={files}&downloadStartSecret={secret}', {
		dir,
		secret,
		files: JSON.stringify(nodes.map(node => node.basename)),
	})
	triggerDownload(url)
}

export const action = new FileAction({
	id: 'download',
	displayName: () => t('files', 'Download'),
	iconSvgInline: () => ArrowDownSvg,

	enabled(nodes: Node[]) {
		return nodes.length > 0 && nodes
			.map(node => node.permissions)
			.every(permission => (permission & Permission.READ) !== 0)
	},

	async exec(node: Node, view: View, dir: string) {
		if (node.type === FileType.Folder) {
			downloadNodes(dir, [node])
			return null
		}

		triggerDownload(node.source)
		return null
	},

	async execBatch(nodes: Node[], view: View, dir: string) {
		if (nodes.length === 1) {
			this.exec(nodes[0], view, dir)
			return [null]
		}

		downloadNodes(dir, nodes)
		return new Array(nodes.length).fill(null)
	},

	order: 30,
})

registerFileAction(action)
