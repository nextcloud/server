/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder, INode, IView } from '@nextcloud/files'
import type { TreeNode } from '../services/FolderTree.ts'

import FolderMultipleSvg from '@mdi/svg/svg/folder-multiple-outline.svg?raw'
import FolderSvg from '@mdi/svg/svg/folder-outline.svg?raw'
import { subscribe } from '@nextcloud/event-bus'
import { FileType, getNavigation, View } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { isSamePath } from '@nextcloud/paths'
import PQueue from 'p-queue'
import {
	folderTreeId,
	getContents,
	getFolderTreeNodes,
	getSourceParent,
	sourceRoot,
} from '../services/FolderTree.ts'
import { useFilesStore } from '../store/files.ts'
import { getPinia } from '../store/index.ts'

interface IFolderTreeView extends IView {
	loading?: boolean
	loaded?: boolean
}

const Navigation = getNavigation()
const queue = new PQueue({ concurrency: 5, intervalCap: 5, interval: 200 })
const isFolderTreeEnabled = loadState('files', 'config', { folder_tree: true }).folder_tree
let showHiddenFiles = loadState('files', 'config', { show_hidden: false }).show_hidden

const folderTreeView: IFolderTreeView = new View({
	id: folderTreeId,

	name: t('files', 'Folder tree'),
	caption: t('files', 'List of your files and folders.'),

	icon: FolderMultipleSvg,
	order: 50, // Below all other views

	getContents,

	async loadChildViews(view) {
		const treeView = view as IFolderTreeView
		if (treeView.loading || treeView.loaded) {
			return
		}

		treeView.loading = true
		try {
			const dir = new URLSearchParams(window.location.search).get('dir') ?? '/'
			const tree = await getFolderTreeNodes(dir, 1, true)
			registerNodeViews(tree, dir)
			treeView.loaded = true

			subscribe('files:node:created', onCreateNode)
			subscribe('files:node:deleted', onDeleteNode)
			subscribe('files:node:moved', onMoveNode)
			subscribe('files:config:updated', onUserConfigUpdated)
		} finally {
			treeView.loading = false
		}
	},
})

/**
 * Register the folder tree feature
 */
export async function registerFolderTreeView() {
	if (!isFolderTreeEnabled) {
		return
	}
	Navigation.register(folderTreeView)
}

/**
 * Helper to register node views in the navigation.
 *
 * @param nodes - The nodes to register
 * @param path - The path to expand by default, if any
 */
async function registerNodeViews(nodes: (TreeNode | IFolder)[], path?: string) {
	const views: IView[] = []
	for (const node of nodes) {
		const isRegistered = Navigation.views.some((view) => view.id === `${folderTreeId}::${node.encodedSource}`)
		// skip hidden files if the setting is disabled
		if (!showHiddenFiles && node.basename.startsWith('.')) {
			if (isRegistered) {
				// and also remove any existing views for hidden files if the setting was toggled
				Navigation.remove(`${folderTreeId}::${node.encodedSource}`)
			}
			continue
		}

		// skip already registered views to avoid duplicates when loading multiple levels
		if (isRegistered) {
			continue
		}

		views.push(generateNodeView(
			node,
			path === node.path || path?.startsWith(node.path + '/') ? true : undefined,
		))
	}
	Navigation.register(...views)
}

/**
 * Generates a navigation view for a given folder tree node or folder.
 *
 * @param node - The folder tree node or folder for which to generate the view.
 * @param expanded - Whether the view should be expanded by default.
 */
function generateNodeView(node: TreeNode | IFolder, expanded?: boolean): IView {
	return {
		id: `${folderTreeId}::${node.encodedSource}`,
		parent: getSourceParent(node.source),

		expanded,
		loaded: expanded,

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
	}
}

/**
 * Generates a function to load child views for a given folder tree node or folder.
 * This function is used as the `loadChildViews` callback in the navigation view.
 *
 * @param node - The folder tree node or folder for which to generate the child view loader function.
 */
function getLoadChildViews(node: TreeNode | IFolder) {
	return async (view: IView): Promise<void> => {
		const treeView = view as IFolderTreeView
		if (treeView.loading || treeView.loaded) {
			return
		}

		treeView.loading = true
		try {
			await updateTreeChildren(node.path)
			treeView.loaded = true
		} finally {
			treeView.loading = false
		}
	}
}

/**
 * Registers child views for the given path. If no path is provided, it registers the root nodes.
 *
 * @param path - The path for which to register child views. Defaults to '/' for root nodes.
 */
async function updateTreeChildren(path: string = '/') {
	await queue.add(async () => {
		const filesStore = useFilesStore(getPinia())
		const cachedNodes = filesStore.getNodesByPath(Navigation.active!.id, path)
		if (cachedNodes.length > 0) {
			// if there are nodes loaded in the path we dont need to fetch from API
			const folders = cachedNodes.filter((node) => node.type === FileType.Folder) as IFolder[]
			registerNodeViews(folders, path)
		} else {
			// otherwise we need to fetch the tree nodes for the path
			const nodes = await getFolderTreeNodes(path, 2)
			registerNodeViews(nodes)
		}
	})
}

/**
 * Remove a folder view from the navigation.
 *
 * @param folder - The folder for which to remove the view
 */
function removeFolderView(folder: IFolder) {
	const viewId = folder.encodedSource
	Navigation.remove(viewId)
}

/**
 * Remove a folder view from the navigation by its source URL.
 *
 * @param source - The source URL of the folder for which to remove the view
 */
function removeFolderViewSource(source: string) {
	Navigation.remove(source)
}

/**
 * Handle node creation events to add new folder tree views to the navigation.
 *
 * @param node - The node that was created
 */
function onCreateNode(node: INode) {
	if (node.type !== FileType.Folder) {
		return
	}
	registerNodeViews([node as IFolder])
}

/**
 * Handle node deletion events to remove the corresponding folder tree views from the navigation.
 *
 * @param node - The node that was deleted
 */
function onDeleteNode(node: INode) {
	if (node.type !== FileType.Folder) {
		return
	}
	removeFolderView(node as IFolder)
}

/**
 * Handle node move events to update the folder tree views accordingly.
 *
 * @param context - the event context
 * @param context.node - The node that was moved
 * @param context.oldSource - the old source URL of the moved node
 */
function onMoveNode({ node, oldSource }) {
	if (node.type !== FileType.Folder) {
		return
	}
	removeFolderViewSource(oldSource)
	registerNodeViews([node as IFolder])

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
		view.parent = getSourceParent(node.source)
		view.params!.dir = view.params!.dir!.replace(oldPath, newPath)
	}
}

/**
 * Handle user config updates, specifically for the "show hidden files" setting,
 * to show hidden folders in the folder tree when enabled and hide them when disabled.
 *
 * @param context - the event context
 * @param context.key - the key of the updated config
 * @param context.value - the new value of the updated config
 */
async function onUserConfigUpdated({ key, value }) {
	if (key === 'show_hidden') {
		showHiddenFiles = value
		await updateTreeChildren()
	}
}
