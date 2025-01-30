/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TreeNode } from '../services/FolderTree.ts'

import PQueue from 'p-queue'
import { FileType, Folder, Node, View, getNavigation } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { emit, subscribe } from '@nextcloud/event-bus'
import { isSamePath } from '@nextcloud/paths'
import { loadState } from '@nextcloud/initial-state'

import FolderSvg from '@mdi/svg/svg/folder.svg?raw'
import FolderMultipleSvg from '@mdi/svg/svg/folder-multiple.svg?raw'

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

const registerTreeChildren = async (path: string = '/') => {
	await queue.add(async () => {
		const nodes = await getFolderTreeNodes(path)
		const promises = nodes.map(node => registerQueue.add(() => registerNodeView(node)))
		await Promise.allSettled(promises)
	})
}

const getLoadChildViews = (node: TreeNode | Folder) => {
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

const registerNodeView = (node: TreeNode | Folder) => {
	const registeredView = Navigation.views.find(view => view.id === node.encodedSource)
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

const removeFolderView = (folder: Folder) => {
	const viewId = folder.encodedSource
	Navigation.remove(viewId)
}

const removeFolderViewSource = (source: string) => {
	Navigation.remove(source)
}

const onCreateNode = (node: Node) => {
	if (node.type !== FileType.Folder) {
		return
	}
	registerNodeView(node)
}

const onDeleteNode = (node: Node) => {
	if (node.type !== FileType.Folder) {
		return
	}
	removeFolderView(node)
}

const onMoveNode = ({ node, oldSource }) => {
	if (node.type !== FileType.Folder) {
		return
	}
	removeFolderViewSource(oldSource)
	registerNodeView(node)

	const newPath = node.source.replace(sourceRoot, '')
	const oldPath = oldSource.replace(sourceRoot, '')
	const childViews = Navigation.views.filter(view => {
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

const onUserConfigUpdated = async ({ key, value }) => {
	if (key === 'show_hidden') {
		showHiddenFiles = value
		await registerTreeChildren()
		// @ts-expect-error No payload
		emit('files:folder-tree:initialized')
	}
}

const registerTreeRoot = () => {
	Navigation.register(new View({
		id: folderTreeId,

		name: t('files', 'All folders'),
		caption: t('files', 'List of your files and folders.'),

		icon: FolderMultipleSvg,
		order: 50, // Below all other views

		getContents,
	}))
}

export const registerFolderTreeView = async () => {
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
