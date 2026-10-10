/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder, INode } from '@nextcloud/files'

import { getCurrentUser } from '@nextcloud/auth'
import { showInfo } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'
import { listen } from '@nextcloud/notify_push'
import { dirname } from '@nextcloud/paths'
import { isPublicShare } from '@nextcloud/sharing/public'
import { onMounted, onUnmounted } from 'vue'
import { getChildrenEtags } from '../services/Files.ts'
import { getNodesById } from '../services/NodesApi.ts'
import { useActiveStore } from '../store/active.ts'
import { useFilesStore } from '../store/files.ts'
import { logger } from '../utils/logger.ts'

/**
 * Views listing the plain folder contents of the user files
 */
const LIVE_VIEWS = ['files']

/**
 * Above this many changed children, compare a light listing of the folder instead
 */
export const MAX_CHANGED_NODES = 200

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
	const fileIds = new Set(pendingFileIds)
	const reconcile = pendingReconcile
	pendingFileIds.clear()
	pendingReconcile = false

	const activeStore = useActiveStore()
	const filesStore = useFilesStore()
	const folder = activeStore.activeFolder as (IFolder & { _children?: string[] }) | undefined
	if (!folder?.fileid || !LIVE_VIEWS.includes(activeStore.activeView?.id ?? '')) {
		return
	}

	// The parent of a changed node is always pushed along with it: when the parent
	// of the current folder changed, the current folder itself may be gone
	const parentId = folder.path === '/' ? undefined : filesStore.getDirectoryByPath('files', dirname(folder.path))?.fileid
	if (parentId !== undefined && fileIds.has(parentId) && !await checkFolderStillExists(folder)) {
		return
	}

	// Children are matched by source: the same file can be listed twice, through two shares
	const children = filesStore.getNodes(folder._children ?? [])
	const folderChanged = fileIds.has(folder.fileid)
	// Ancestors of a changed node are pushed along with it, only children of the current folder matter.
	// A new file always comes with its parent, so unknown ids only count when the current folder changed.
	const changedIds = [...fileIds].filter((fileId) => fileId !== folder.fileid
		&& (children.some((child) => child.fileid === fileId)
			|| (folderChanged && filesStore.getNodesById(String(fileId)).length === 0)))

	if (changedIds.length > 0 && changedIds.length <= MAX_CHANGED_NODES) {
		await refreshNodes(folder, children, changedIds)
	} else if (changedIds.length > 0 || folderChanged || (reconcile && folder.path === '/')) {
		// Too many changes, or only the folder changed: a child was deleted and notify_push
		// does not send the id of files moved to the trash bin before nextcloud/notify_push#776
		await reconcileFolder(folder, children)
	}
}

/**
 * Fetch the current folder and the given nodes, and apply them to the files list
 *
 * @param folder - The current folder
 * @param children - The current children
 * @param fileIds - The ids of the changed nodes
 */
async function refreshNodes(folder: IFolder, children: INode[], fileIds: number[]) {
	const nodes = await getNodesById([folder.fileid!, ...fileIds], { dir: folder.path })
	const folderNode = nodes.find((node) => node.source === folder.source)
	if (!folderNode) {
		await checkFolderStillExists(folder)
		return
	}

	logger.debug('Refreshing changed nodes in the current folder', { folder, fileIds, nodes })
	// Without event: the files list refetches the whole folder when the current folder is updated
	useFilesStore().updateNodes([withKnownAttributes(folderNode, folder)])

	const found = new Set<string>()
	for (const node of nodes) {
		if (node === folderNode) {
			continue
		}

		found.add(node.source)
		const known = children.find((child) => child.source === node.source)
		const moved = children.find((child) => child.fileid === node.fileid && !nodes.some((other) => other.source === child.source))
		if (known) {
			emit('files:node:updated', withKnownAttributes(node, known))
		} else if (moved) {
			// Renamed, or moved into a subfolder
			found.add(moved.source)
			emit('files:node:moved', { node: withKnownAttributes(node, moved), oldSource: moved.source })
		} else if (dirname(node.source) === folder.source) {
			emit('files:node:created', node)
		}
	}

	// Requested children that were not found are not within this folder anymore
	children
		.filter((child) => fileIds.includes(child.fileid!) && !found.has(child.source))
		.forEach((child) => emit('files:node:deleted', child))
}

/**
 * The nodes API does not provide every attribute of a listing yet,
 * keep the ones it does not provide from the node we already have
 *
 * @param node - The fetched node
 * @param known - The node we already have
 */
function withKnownAttributes(node: INode, known: INode): INode {
	for (const [key, value] of Object.entries(known.attributes)) {
		if (!(key in node.attributes)) {
			node.attributes[key] = value
		}
	}
	return node
}

/**
 * Check that the current folder is still at its path.
 * If it moved, follow it. If it was deleted, go to its closest remaining ancestor.
 *
 * @param folder - The current folder
 * @return Whether the folder is still at its path
 */
async function checkFolderStillExists(folder: IFolder): Promise<boolean> {
	const filesStore = useFilesStore()
	const parts = folder.path.split('/').filter(Boolean).slice(0, -1)
	const ancestors = ['/', ...parts.map((_, index) => '/' + parts.slice(0, index + 1).join('/'))]
		.map((path) => filesStore.getDirectoryByPath('files', path))
		.filter((node): node is IFolder => node?.fileid !== undefined)
	const nodes = await getNodesById([folder.fileid!, ...ancestors.map((node) => node.fileid!)])

	if (nodes.some((node) => node.source === folder.source)) {
		return true
	}

	const movedTo = nodes.find((node) => node.fileid === folder.fileid)
	if (movedTo) {
		logger.debug('The current folder was moved', { folder, movedTo })
		emit('files:node:moved', { node: movedTo, oldSource: folder.source })
		goToFolder(movedTo)
		return false
	}

	const remaining = ancestors.reverse()
		.map((ancestor) => nodes.find((node) => node.source === ancestor.source))
		.find((node) => node !== undefined)
	logger.debug('The current folder was deleted', { folder, remaining })
	emit('files:node:deleted', folder)
	showInfo(t('files', '"{name}" was deleted', { name: folder.displayname }))
	goToFolder(remaining)
	return false
}

/**
 * Open a folder in the files list, the root folder if none
 *
 * @param folder - The folder to open
 */
function goToFolder(folder?: INode) {
	const params = { ...window.OCP.Files.Router.params }
	delete params.fileid
	window.OCP.Files.Router.goToRoute(
		null,
		folder?.fileid ? { ...params, fileid: String(folder.fileid) } : params,
		{ ...window.OCP.Files.Router.query, dir: folder?.path ?? '/' },
	)
}

/**
 * Compare the current children with a light listing of the folder,
 * then fetch the new and changed nodes only
 *
 * @param folder - The current folder
 * @param children - The current children
 */
async function reconcileFolder(folder: IFolder, children: INode[]) {
	const etags = await getChildrenEtags(folder.path)

	const changedIds = [...etags]
		.filter(([fileId, etag]) => !children.some((child) => child.fileid === fileId && child.attributes.etag === etag))
		.map(([fileId]) => fileId)
	const deleted = children.filter((node) => !etags.has(node.fileid!))

	logger.debug('Reconciled the current folder', { folder, changedIds, deleted })
	deleted.forEach((node) => emit('files:node:deleted', node))
	await refreshNodes(folder, children, changedIds)
}
