/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Node, View } from '@nextcloud/files'
import { FileAction, FileType, DefaultType } from '@nextcloud/files'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'

import ArrowDownSvg from '@mdi/svg/svg/arrow-down.svg?raw'

import { isDownloadable } from '../utils/permissions'
import { usePathsStore } from '../store/paths'
import { getPinia } from '../store'
import { useFilesStore } from '../store/files'
import { emit } from '@nextcloud/event-bus'

/**
 * Trigger downloading a file.
 *
 * @param url The url of the asset to download
 * @param name Optionally the recommended name of the download (browsers might ignore it)
 */
async function triggerDownload(url: string, name?: string) {
	// try to see if the resource is still available
	await axios.head(url)

	const hiddenElement = document.createElement('a')
	hiddenElement.download = name ?? ''
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

/**
 * Download the given nodes.
 *
 * If only one node is given, it will be downloaded directly.
 * If multiple nodes are given, they will be zipped and downloaded.
 *
 * @param nodes The node(s) to download
 */
async function downloadNodes(nodes: Node[]) {
	let url: URL

	if (nodes.length === 1) {
		if (nodes[0].type === FileType.File) {
			await triggerDownload(nodes[0].encodedSource, nodes[0].displayname)
			return
		} else {
			url = new URL(nodes[0].encodedSource)
			url.searchParams.append('accept', 'zip')
		}
	} else {
		url = new URL(nodes[0].encodedSource)
		let base = url.pathname
		for (const node of nodes.slice(1)) {
			base = longestCommonPath(base, (new URL(node.encodedSource).pathname))
		}
		url.pathname = base

		// The URL contains the path encoded so we need to decode as the query.append will re-encode it
		const filenames = nodes.map((node) => decodeURIComponent(node.encodedSource.slice(url.href.length + 1)))
		url.searchParams.append('accept', 'zip')
		url.searchParams.append('files', JSON.stringify(filenames))
	}

	if (url.pathname.at(-1) !== '/') {
		url.pathname = `${url.pathname}/`
	}

	await triggerDownload(url.href)
}

/**
 * Get the current directory node for the given view and path.
 * TODO: ideally the folder would directly be passed as exec params
 *
 * @param view The current view
 * @param directory The directory path
 * @return The current directory node or null if not found
 */
function getCurrentDirectory(view: View, directory: string): Node | null {
	const filesStore = useFilesStore(getPinia())
	const pathsStore = usePathsStore(getPinia())
	if (!view?.id) {
		return null
	}

	if (directory === '/') {
		return filesStore.getRoot(view.id) || null
	}
	const fileId = pathsStore.getPath(view.id, directory)!
	return filesStore.getNode(fileId) || null
}

export const action = new FileAction({
	id: 'download',
	default: DefaultType.DEFAULT,

	displayName: () => t('files', 'Download'),
	iconSvgInline: () => ArrowDownSvg,

	enabled(nodes: Node[], view: View) {
		if (nodes.length === 0) {
			return false
		}

		// We can only download dav files and folders.
		if (nodes.some(node => !node.isDavResource)) {
			return false
		}

		// Trashbin does not allow batch download
		if (nodes.length > 1 && view.id === 'trashbin') {
			return false
		}

		return nodes.every(isDownloadable)
	},

	async exec(node: Node) {
		try {
			await downloadNodes([node])
		} catch (e) {
			showError(t('files', 'The requested file is not available.'))
			emit('files:node:deleted', node)
		}
		return null
	},

	async execBatch(nodes: Node[], view: View, dir: string) {
		try {
			await downloadNodes(nodes)
		} catch (e) {
			showError(t('files', 'The requested files are not available.'))
			// Try to reload the current directory to update the view
			const directory = getCurrentDirectory(view, dir)!
			emit('files:node:updated', directory)
		}
		return new Array(nodes.length).fill(null)
	},

	order: 30,
})
