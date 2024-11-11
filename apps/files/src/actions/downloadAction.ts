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
import { FileAction, Permission, Node, FileType, View, DefaultType } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import ArrowDownSvg from '@mdi/svg/svg/arrow-down.svg?raw'

const triggerDownload = function(url: string) {
	const hiddenElement = document.createElement('a')
	hiddenElement.download = ''
	hiddenElement.href = url
	hiddenElement.click()
}

/**
 * Find the longest common path prefix of both input paths
 * @param first The first path
 * @param second The second path
 */
function longestCommonPath(first: string, second: string): string {
	const firstSegments = first.split('/').filter(Boolean)
	const secondSegments = second.split('/').filter(Boolean)
	let base = '/'
	for (const [index, segment] of firstSegments.entries()) {
		if (index >= second.length) {
			break
		}
		if (segment !== secondSegments[index]) {
			break
		}
		const sep = base === '/' ? '' : '/'
		base = `${base}${sep}${segment}`
	}
	return base
}

/**
 * Handle downloading multiple nodes
 * @param nodes The nodes to download
 */
function downloadNodes(nodes: Node[]): void {
	// Remove nodes that are already included in parent folders
	// Example: Download A/foo.txt and A will only return A as A/foo.txt is already included
	const filteredNodes = nodes.filter((node) => {
		const parent = nodes.find((other) => (
			other.type === FileType.Folder
				&& node.path.startsWith(`${other.path}/`)
		))
		return parent === undefined
	})

	let base = filteredNodes[0].dirname
	for (const node of filteredNodes.slice(1)) {
		base = longestCommonPath(base, node.dirname)
	}
	base = base || '/'

	// Remove the common prefix
	const filenames = filteredNodes.map((node) => node.path.slice(base === '/' ? 1 : (base.length + 1)))

	const secret = Math.random().toString(36).substring(2)
	const url = generateUrl('/apps/files/ajax/download.php?dir={base}&files={files}&downloadStartSecret={secret}', {
		base,
		secret,
		files: JSON.stringify(filenames),
	})
	triggerDownload(url)
}

const isDownloadable = function(node: Node) {
	if ((node.permissions & Permission.READ) === 0) {
		return false
	}

	// If the mount type is a share, ensure it got download permissions.
	if (node.attributes['mount-type'] === 'shared') {
		const shareAttributes = JSON.parse(node.attributes['share-attributes'] ?? 'null')
		const downloadAttribute = shareAttributes?.find?.((attribute: { scope: string; key: string }) => attribute.scope === 'permissions' && attribute.key === 'download')
		if (downloadAttribute !== undefined && downloadAttribute.enabled === false) {
			return false
		}
	}

	return true
}

export const action = new FileAction({
	id: 'download',
	default: DefaultType.DEFAULT,

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
			downloadNodes([node])
			return null
		}

		triggerDownload(node.encodedSource)
		return null
	},

	async execBatch(nodes: Node[], view: View, dir: string) {
		if (nodes.length === 1) {
			this.exec(nodes[0], view, dir)
			return [null]
		}

		downloadNodes(nodes)
		return new Array(nodes.length).fill(null)
	},

	order: 30,
})
