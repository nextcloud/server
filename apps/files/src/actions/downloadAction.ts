/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { FileAction, Node, FileType, DefaultType } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { isDownloadable } from '../utils/permissions'

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
	let base = ''
	for (const [index, segment] of firstSegments.entries()) {
		if (index >= second.length) {
			break
		}
		if (segment !== secondSegments[index]) {
			break
		}
		const sep = base === '' ? '' : '/'
		base = `${base}${sep}${segment}`
	}
	return base
}

const downloadNodes = function(nodes: Node[]) {
	let url: URL

	if (nodes.length === 1) {
		if (nodes[0].type === FileType.File) {
			return triggerDownload(nodes[0].encodedSource)
		} else {
			url = new URL(nodes[0].encodedSource)
			url.searchParams.append('accept', 'zip')
		}
	} else {
		url = new URL(nodes[0].source)
		let base = url.pathname
		for (const node of nodes.slice(1)) {
			base = longestCommonPath(base, (new URL(node.source).pathname))
		}
		url.pathname = base

		// The URL contains the path encoded so we need to decode as the query.append will re-encode it
		const filenames = nodes.map((node) => decodeURI(node.encodedSource.slice(url.href.length + 1)))
		url.searchParams.append('accept', 'zip')
		url.searchParams.append('files', JSON.stringify(filenames))
	}

	if (url.pathname.at(-1) !== '/') {
		url.pathname = `${url.pathname}/`
	}

	return triggerDownload(url.href)
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

		// We can only download dav files and folders.
		if (nodes.some(node => !node.isDavRessource)) {
			return false
		}

		return nodes.every(isDownloadable)
	},

	async exec(node: Node) {
		downloadNodes([node])
		return null
	},

	async execBatch(nodes: Node[]) {
		downloadNodes(nodes)
		return new Array(nodes.length).fill(null)
	},

	order: 30,
})
