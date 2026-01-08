/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Folder, Node } from '@nextcloud/files'
import type { TreeNode } from '../services/FolderTree.ts'

import FolderMultipleSvg from '@mdi/svg/svg/folder-multiple-outline.svg?raw'
import FolderSvg from '@mdi/svg/svg/folder-outline.svg?raw'
import { emit, subscribe } from '@nextcloud/event-bus'
import { FileType, getNavigation, View } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { isSamePath } from '@nextcloud/paths'
import PQueue from 'p-queue'
import {
	folderTreeId,
	getContents,
	getFolderTreeNodes,
	getSourceParent,
	sourceRoot,
} from '../services/FolderTree.ts'

const isFolderTreeEnabled = loadState('files', 'config', { folder_tree: true }).folder_tree

let showHiddenFiles = loadState('files', 'config', { show_hidden: false }).show_hidden

const Navigation = getNavigation()

const queue = new PQueue({ concurrency: 5, intervalCap: 5, interval: 200 })

const registerQueue = new PQueue({ concurrency: 5, intervalCap: 5, interval: 200 })

/**
 *
 * @param path
 */
async function registerTreeChildren(path: string = '/') {
	await queue.add(async () => {
		// preload up to 2 depth levels for faster navigation
		const nodes = await getFolderTreeNodes(path, 2)
		const promises = nodes.map((node) => registerQueue.add(() => registerNodeView(node)))
		await Promise.allSettled(promises)
	})
}

/**
 *
 * @param node
 */
function getLoadChildViews(node: TreeNode | Folder) {
	return async (view: View): Promise<void> => {
		// @ts-expect-error Custom property on View instance
		if (view.loading || view.loaded) {
			return
		}
		// @ts-expect-error Custom property
		view.loading = true
		await registerTreeChildren(node.path)
		// @ts-expect-error Custom property
		view.loading = false
		// @ts-expect-error Custom property
		view.loaded = true
		// @ts-expect-error No payload
		emit('files:navigation:updated')
		// @ts-expect-error No payload
		emit('files:folder-tree:expanded')
	}
}

/**
 *
 * @param node
 */
function registerNodeView(node: TreeNode | Folder) {
	const registeredView = Navigation.views.find((view) => view.id === node.encodedSource)
	if (registeredView) {
		Navigation.remove(registeredView.id)
	}
	if (!showHiddenFiles && node.basename.startsWith('.')) {
		return
	}
	Navigation.register(new View({
		id: node.encodedSource,
		parent: getSourceParent(node.source),

		// @ts-expect-error Casing differences
		name: node.displayName ?? node.displayname ?? node.basename,

		icon: FolderSvg,

		getContents,
		loadChildViews: getLoadChildViews(node),

		params: {
			view: folderTreeId,
			fileid: String(node.fileid), // Needed for matching exact routes
			dir: node.path,
		},
	}))
}

/**
 *
 * @param folder
 */
function removeFolderView(folder: Folder) {
	const viewId = folder.encodedSource
	Navigation.remove(viewId)
}

/**
 *
 * @param source
 */
function removeFolderViewSource(source: string) {
	Navigation.remove(source)
}

/**
 *
 * @param node
 */
function onCreateNode(node: Node) {
	if (node.type !== FileType.Folder) {
		return
	}
	registerNodeView(node)
}

/**
 *
 * @param node
 */
function onDeleteNode(node: Node) {
	if (node.type !== FileType.Folder) {
		return
	}
	removeFolderView(node)
}

/**
 *
 * @param root0
 * @param root0.node
 * @param root0.oldSource
 */
function onMoveNode({ node, oldSource }) {
	if (node.type !== FileType.Folder) {
		return
	}
	removeFolderViewSource(oldSource)
	registerNodeView(node)

	const newPath = node.source.replace(sourceRoot, '')
	const oldPath = oldSource.replace(sourceRoot, '')
	const childViews = Navigation.views.filter((view) => {
		if (!view.params?.dir) {
			return false
		}
		if (isSamePath(view.params.dir, oldPath)) {
			return false
		}
		return view.params.dir.startsWith(oldPath)
	})
	for (const view of childViews) {
		// @ts-expect-error FIXME Allow setting parent
		view.parent = getSourceParent(node.source)
		// @ts-expect-error dir param is defined
		view.params.dir = view.params.dir.replace(oldPath, newPath)
	}
}

/**
 *
 * @param root0
 * @param root0.key
 * @param root0.value
 */
async function onUserConfigUpdated({ key, value }) {
	if (key === 'show_hidden') {
		showHiddenFiles = value
		await registerTreeChildren()
		// @ts-expect-error No payload
		emit('files:folder-tree:initialized')
	}
}

/**
 *
 */
function registerTreeRoot() {
	Navigation.register(new View({
		id: folderTreeId,

		name: t('files', 'Folder tree'),
		caption: t('files', 'List of your files and folders.'),

		icon: FolderMultipleSvg,
		order: 50, // Below all other views

		getContents,
	}))
}

/**
 *
 */
export async function registerFolderTreeView() {
	if (!isFolderTreeEnabled) {
		return
	}
	registerTreeRoot()
	await registerTreeChildren()
	subscribe('files:node:created', onCreateNode)
	subscribe('files:node:deleted', onDeleteNode)
	subscribe('files:node:moved', onMoveNode)
	subscribe('files:config:updated', onUserConfigUpdated)
	// @ts-expect-error No payload
	emit('files:folder-tree:initialized')
}
