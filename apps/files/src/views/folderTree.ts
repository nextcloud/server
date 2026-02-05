/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder, INode, IView } from '@nextcloud/files'
import type { TreeNode } from '../services/FolderTree.ts'

import FolderMultipleSvg from '@mdi/svg/svg/folder-multiple-outline.svg?raw'
import FolderSvg from '@mdi/svg/svg/folder-outline.svg?raw'
import { getCurrentUser } from '@nextcloud/auth'
import { subscribe } from '@nextcloud/event-bus'
import { FileType, Folder, getNavigation, View } from '@nextcloud/files'
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
import { useActiveStore } from '../store/active.ts'
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

/**
 * Register the folder tree feature
 */
export async function registerFolderTreeView() {
	if (!isFolderTreeEnabled) {
		return
	}
	registerTreeRoot()

	subscribe('files:list:initialized', () => registerTreeChildren())
}

/**
 * Postponed registration of tree children to ensure the root view is registered and rendered first.
 */
async function registerTreeChildren() {
	// because the files app is initialized we now have access to the stores
	const activeStore = useActiveStore(getPinia())
	const filesStore = useFilesStore(getPinia())
	const currentPath = activeStore.activeFolder?.path ?? '/'
	const views: IFolderTreeView[] = []

	// if we are in a subfolder, register all parent folders first
	const segments = currentPath.slice(1).split('/')
	if (segments[0] !== '') {
		const sourceSegments = activeStore.activeFolder!.source.split('/')
		for (let i = 1; i <= segments.length; i++) {
			const source = sourceSegments.slice(0, -i).join('/')
			const node = filesStore.getNode(source)
			if (node) {
				// we have the node already loaded
				views.push(generateNodeView(node as IFolder))
			} else {
				// fake this parent folder until we have it loaded
				views.push(generateNodeView(new Folder({
					owner: getCurrentUser()!.uid,
					root: activeStore.activeFolder!.root,
					source,
				})))
			}
		}
	}
	// finally also add all views for folders in current view
	const folders = filesStore.getNodesByPath(activeStore.activeView!.id, currentPath)
		.filter((node) => node.type === FileType.Folder) as IFolder[]

	// mark the current folder as loaded to avoid loading it again when navigating to it
	const activeFolderView = views.find((view) => view.id === activeStore.activeFolder!.encodedSource)
	if (activeFolderView) {
		activeFolderView.loaded = true
	}

	if (folders.length > 0) {
		views.push(...folders.map(generateNodeView))
	}
	if (views.length > 0) {
		Navigation.register(...views)
	}

	subscribe('files:node:created', onCreateNode)
	subscribe('files:node:deleted', onDeleteNode)
	subscribe('files:node:moved', onMoveNode)
	subscribe('files:config:updated', onUserConfigUpdated)
}

/**
 * Registers child views for the given path. If no path is provided, it registers the root nodes.
 *
 * @param path - The path for which to register child views. Defaults to '/' for root nodes.
 */
async function updateTreeChildren(path: string = '/') {
	await queue.add(async () => {
		// preload 2 depth levels by default for faster navigation
		const nodes = await getFolderTreeNodes(path, 2)
		registerNodeViews(nodes)
	})
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
 * Generates a navigation view for a given folder tree node or folder.
 *
 * @param node - The folder tree node or folder for which to generate the view.
 */
function generateNodeView(node: TreeNode | IFolder): IView {
	return {
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
	}
}

/**
 * Helper to register node views in the navigation.
 *
 * @param nodes - The nodes to register
 */
async function registerNodeViews(nodes: (TreeNode | IFolder)[]) {
	const views: IView[] = []
	for (const node of nodes) {
		const isRegistered = Navigation.views.some((view) => view.id === node.encodedSource)
		// skip hidden files if the setting is disabled
		if (!showHiddenFiles && node.basename.startsWith('.')) {
			if (isRegistered) {
				// and also remove any existing views for hidden files if the setting was toggled
				Navigation.remove(node.encodedSource)
			}
			continue
		}

		// skip already registered views to avoid duplicates when loading multiple levels
		if (isRegistered) {
			continue
		}

		views.push(generateNodeView(node))
	}
	Navigation.register(...views)
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
 *
 * @param node
 */
function onCreateNode(node: INode) {
	if (node.type !== FileType.Folder) {
		return
	}
	registerNodeViews([node as IFolder])
}

/**
 *
 * @param node
 */
function onDeleteNode(node: INode) {
	if (node.type !== FileType.Folder) {
		return
	}
	removeFolderView(node as IFolder)
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
 *
 * @param root0
 * @param root0.key
 * @param root0.value
 */
async function onUserConfigUpdated({ key, value }) {
	if (key === 'show_hidden') {
		showHiddenFiles = value
		await updateTreeChildren()
	}
}

/**
 * Register the root view of the folder tree in the navigation.
 * This is the entry point for the folder tree and will allow users to access their files and folders through the navigation menu.
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
