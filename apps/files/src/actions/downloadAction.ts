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
import { FileAction, Permission, Node, FileType, View } from '@nextcloud/files'
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

const isDownloadable = function(node: Node) {
	if ((node.permissions & Permission.READ) === 0) {
		return false
	}

	// If the mount type is a share, ensure it got download permissions.
	if (node.attributes['mount-type'] === 'shared') {
		const downloadAttribute = JSON.parse(node.attributes['share-attributes']).find((attribute: { scope: string; key: string }) => attribute.scope === 'permissions' && attribute.key === 'download')
		if (downloadAttribute !== undefined && downloadAttribute.enabled === false) {
			return false
		}
	}

	return true
}

export const action = new FileAction({
	id: 'download',
	displayName: () => t('files', 'Download'),
	iconSvgInline: () => ArrowDownSvg,

	enabled(nodes: Node[]) {
		if (nodes.length === 0) {
			return false
		}

		// We can download direct dav files. But if we have
		// some folders, we need to use the /apps/files/ajax/download.php
		// endpoint, which only supports user root folder.
		if (nodes.some(node => node.type === FileType.Folder)
			&& nodes.some(node => !node.root?.startsWith('/files'))) {
			return false
		}

		return nodes.every(isDownloadable)
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
