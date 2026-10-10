/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder, INode } from '@nextcloud/files'

import { getCurrentUser } from '@nextcloud/auth'
import { emit } from '@nextcloud/event-bus'
import { listen } from '@nextcloud/notify_push'
import { dirname } from '@nextcloud/paths'
import { isPublicShare } from '@nextcloud/sharing/public'
import { onMounted, onUnmounted } from 'vue'
import { getChildrenEtags } from '../services/Files.ts'
import { MAX_SEARCH_FILE_IDS, searchNodesById } from '../services/WebDavSearch.ts'
import { useActiveStore } from '../store/active.ts'
import { useFilesStore } from '../store/files.ts'
import { logger } from '../utils/logger.ts'

/**
 * Views listing the plain folder contents of the user files
 */
const LIVE_VIEWS = ['files']

const pendingFileIds = new Set<number>()
let pendingReconcile = false
let flushing: Promise<void> | null = null
let mountedLists = 0
let registered = false

/**
 * Keep the current folder of the files list up to date with changes
 * made elsewhere, from the file ids pushed by the notify_push app.
 *
 * Only the changed nodes are fetched, never the whole folder,
 * except when the push does not say which child changed.
 */
export function useLiveFolderRefresh() {
	onMounted(() => {
		mountedLists++
		register()
	})
	onUnmounted(() => {
		mountedLists--
		if (mountedLists === 0) {
			pendingFileIds.clear()
			pendingReconcile = false
		}
	})
}

/**
 * Start listening to notify_push, once per page.
 * There is no way to stop listening, the handlers check whether a list is mounted.
 */
function register() {
	if (registered || isPublicShare() || !getCurrentUser()) {
		return
	}
	registered = true

	const available = listen('notify_file_id', (_, fileIds: number[]) => {
		fileIds.forEach((fileId) => pendingFileIds.add(fileId))
		schedule()
	})
	if (!available) {
		logger.debug('notify_push is not available, the files list will not refresh by itself')
		return
	}

	// Pushed without file ids when shares or group memberships change,
	// which adds or removes mount points in the root folder
	listen('notify_file', () => {
		pendingReconcile = true
		schedule()
	})

	document.addEventListener('visibilitychange', () => schedule())
}

/**
 * Apply the pending changes, one batch at a time and only while the page is visible
 */
function schedule() {
	if (document.visibilityState === 'hidden' || flushing || mountedLists === 0) {
		return
	}

	if (pendingFileIds.size === 0 && !pendingReconcile) {
		return
	}

	flushing = flush()
		.catch((error) => logger.error('Could not refresh the files list', { error }))
		.finally(() => {
			flushing = null
			schedule()
		})
}

/**
 * Apply the pending changes to the current folder
 */
async function flush() {
	const fileIds = [...pendingFileIds]
	const reconcile = pendingReconcile
	pendingFileIds.clear()
	pendingReconcile = false

	const activeStore = useActiveStore()
	const filesStore = useFilesStore()
	const folder = activeStore.activeFolder as (IFolder & { _children?: string[] }) | undefined
	if (!folder?.fileid || !LIVE_VIEWS.includes(activeStore.activeView?.id ?? '')) {
		return
	}

	const children = new Map(filesStore.getNodes(folder._children ?? [])
		.filter((node) => node.fileid !== undefined)
		.map((node) => [node.fileid!, node]))

	// Ancestors of the changed node are pushed along with it, we only need the ids
	// of children of the current folder or of nodes we never saw (new files)
	const changedIds = fileIds.filter((fileId) => fileId !== folder.fileid
		&& (children.has(fileId) || filesStore.getNodesById(String(fileId)).length === 0))

	if (changedIds.length > 0) {
		await refreshNodes(folder, children, changedIds)
	} else if (reconcile ? folder.path === '/' : fileIds.includes(folder.fileid)) {
		// Only the folder itself changed: a child was deleted, the trash bin
		// drops the id of the deleted node, or a share appeared in the root
		await reconcileFolder(folder, children)
	}
}

/**
 * Fetch the given nodes and apply them to the current folder
 *
 * @param folder - The current folder
 * @param children - The current children, by file id
 * @param fileIds - The ids of the changed nodes
 */
async function refreshNodes(folder: IFolder, children: Map<number, INode>, fileIds: number[]) {
	const nodes: INode[] = []
	for (let i = 0; i < fileIds.length; i += MAX_SEARCH_FILE_IDS) {
		nodes.push(...await searchNodesById(fileIds.slice(i, i + MAX_SEARCH_FILE_IDS), { dir: folder.path }))
	}

	logger.debug('Refreshing changed nodes in the current folder', { folder, fileIds, nodes })
	for (const node of nodes) {
		const child = children.get(node.fileid!)
		const isChild = dirname(node.source) === folder.source
		if (child && child.source !== node.source) {
			// Renamed, or moved into a subfolder
			emit('files:node:moved', { node, oldSource: child.source })
		} else if (child) {
			emit('files:node:updated', node)
		} else if (isChild) {
			emit('files:node:created', node)
		}
	}

	// Requested children that were not found are not within this folder anymore
	const foundIds = new Set(nodes.map((node) => node.fileid))
	for (const fileId of fileIds) {
		const child = children.get(fileId)
		if (child && !foundIds.has(fileId)) {
			emit('files:node:deleted', child)
		}
	}
}

/**
 * Compare the current children with a light listing of the folder,
 * then fetch the new and changed nodes only
 *
 * @param folder - The current folder
 * @param children - The current children, by file id
 */
async function reconcileFolder(folder: IFolder, children: Map<number, INode>) {
	const etags = await getChildrenEtags(folder.path)

	const changedIds = [...etags]
		.filter(([fileId, etag]) => children.get(fileId)?.attributes.etag !== etag)
		.map(([fileId]) => fileId)
	const deleted = [...children.values()].filter((node) => !etags.has(node.fileid!))

	logger.debug('Reconciled the current folder', { folder, changedIds, deleted })
	deleted.forEach((node) => emit('files:node:deleted', node))
	if (changedIds.length > 0) {
		await refreshNodes(folder, children, changedIds)
	}
}
