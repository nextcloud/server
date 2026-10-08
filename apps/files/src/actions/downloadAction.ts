/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFileAction, INode, IView } from '@nextcloud/files'
import type { FileStat, ResponseDataDetailed } from 'webdav'

import ArrowDownSvg from '@mdi/svg/svg/arrow-down.svg?raw'
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { DefaultType, FileType } from '@nextcloud/files'
import { getClient } from '@nextcloud/files/dav'
import { t } from '@nextcloud/l10n'
import { join } from 'path'
import { useFilesStore } from '../store/files.ts'
import { pinia } from '../store/index.ts'
import { usePathsStore } from '../store/paths.ts'
import { logger } from '../utils/logger.ts'
import { isDownloadable } from '../utils/permissions.ts'

/**
 * Asked only when a single file is downloaded.
 * Listing every file would sign a URL for the whole folder, and those URLs expire.
 */
const directDownloadPropfind = `<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns" xmlns:nc="http://nextcloud.org/ns">
	<d:prop>
		<d:getetag />
		<oc:downloadURL />
		<nc:download-url-expiration />
	</d:prop>
</d:propfind>`

export const action: IFileAction = {
	id: 'download',
	default: DefaultType.DEFAULT,

	displayName: () => t('files', 'Download'),
	iconSvgInline: () => ArrowDownSvg,

	enabled({ nodes, view }): boolean {
		if (nodes.length === 0) {
			return false
		}

		// We can only download dav files and folders.
		if (nodes.some((node) => !node.isDavResource)) {
			return false
		}

		// Trashbin does not allow batch download
		if (nodes.length > 1 && view.id === 'trashbin') {
			return false
		}

		return nodes.every(isDownloadable)
	},

	async exec({ nodes }) {
		try {
			await downloadNodes(nodes)
		} catch (error) {
			showError(t('files', 'The requested file is not available.'))
			logger.error('The requested file is not available.', { error })
			emit('files:node:deleted', nodes[0])
		}
		return null
	},

	async execBatch({ nodes, view, folder }) {
		try {
			await downloadNodes(nodes)
		} catch (error) {
			showError(t('files', 'The requested files are not available.'))
			logger.error('The requested files are not available.', { error })
			// Try to reload the current directory to update the view
			const directory = getCurrentDirectory(view, folder.path)!
			emit('files:node:updated', directory)
		}
		return new Array(nodes.length).fill(null)
	},

	order: 30,
}

/**
 * Trigger downloading a file.
 *
 * @param url The url of the asset to download
 * @param name Optionally the recommended name of the download (browsers might ignore it)
 * @param checkAvailability Probe the URL with HEAD before navigating to it
 */
async function triggerDownload(url: string, name?: string, checkAvailability = true) {
	// A pre-signed object-storage URL is signed for GET. HEAD against it is rejected.
	if (checkAvailability) {
		await axios.head(url)
	}

	const hiddenElement = document.createElement('a')
	hiddenElement.download = name ?? ''
	hiddenElement.href = url
	hiddenElement.click()
}

/**
 * Pre-signed object-storage URL for one file, when the server offers one that is still valid.
 *
 * @param node The file to download
 */
async function getDirectDownloadUrl(node: INode): Promise<string | null> {
	try {
		const response = await getClient().stat(join(node.root, node.path), {
			details: true,
			data: directDownloadPropfind,
		}) as ResponseDataDetailed<FileStat>
		const props = response.data.props ?? {}
		const url = props.downloadURL
		if (typeof url !== 'string' || url === '') {
			return null
		}

		const expiration = Number(props['download-url-expiration'])
		if (Number.isFinite(expiration) && expiration * 1000 <= Date.now()) {
			return null
		}
		return url
	} catch (error) {
		logger.debug('Direct download URL is not available, downloading through Nextcloud.', { error })
		return null
	}
}

/**
 * Find the longest common path prefix of both input paths
 *
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
async function downloadNodes(nodes: INode[]) {
	let url: URL

	if (!nodes[0]) {
		throw new Error('No nodes to download')
	}

	if (nodes.length === 1) {
		if (nodes[0].type === FileType.File) {
			const directUrl = await getDirectDownloadUrl(nodes[0])
			if (directUrl) {
				await triggerDownload(directUrl, nodes[0].displayname, false)
				return
			}
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
function getCurrentDirectory(view: IView, directory: string): INode | null {
	const filesStore = useFilesStore(pinia)
	const pathsStore = usePathsStore(pinia)
	if (!view?.id) {
		return null
	}

	if (directory === '/') {
		return filesStore.getRoot(view.id) || null
	}
	const fileId = pathsStore.getPath(view.id, directory)!
	return filesStore.getNode(fileId) || null
}
