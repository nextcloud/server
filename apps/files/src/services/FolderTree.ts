/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ContentsWithRoot } from '@nextcloud/files'

import { CancelablePromise } from 'cancelable-promise'
import {
	davRemoteURL,
	Folder,
} from '@nextcloud/files'
import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { dirname, encodePath } from '@nextcloud/paths'

import { getContents as getFiles } from './Files.ts'

export const folderTreeId = 'folders'
export const sourceRoot = `${davRemoteURL}/files/${getCurrentUser()?.uid}`

interface TreeNodeData {
	id: number,
	displayName?: string,
	// eslint-disable-next-line no-use-before-define
	children?: Tree,
}

interface Tree {
	[basename: string]: TreeNodeData,
}

export interface TreeNode {
	source: string,
	path: string,
	fileid: number,
	basename: string,
	displayName?: string,
}

const getTreeNodes = (tree: Tree, nodes: TreeNode[] = [], currentPath: string = ''): TreeNode[] => {
	for (const basename in tree) {
		const path = `${currentPath}/${basename}`
		const node: TreeNode = {
			source: `${sourceRoot}${path}`,
			path,
			fileid: tree[basename].id,
			basename,
			displayName: tree[basename].displayName,
		}
		nodes.push(node)
		if (tree[basename].children) {
			getTreeNodes(tree[basename].children, nodes, path)
		}
	}
	return nodes
}

export const getFolderTreeNodes = async (): Promise<TreeNode[]> => {
	const { data: tree } = await axios.get<Tree>(generateOcsUrl('/apps/files/api/v1/folder-tree'))
	const nodes = getTreeNodes(tree)
	return nodes
}

export const getContents = (path: string): CancelablePromise<ContentsWithRoot> => getFiles(path)

export const encodeSource = (source: string): string => {
	const { origin } = new URL(source)
	return origin + encodePath(source.slice(origin.length))
}

export const getSourceParent = (source: string): string => {
	const parent = dirname(source)
	if (parent === sourceRoot) {
		return folderTreeId
	}
	return encodeSource(parent)
}

export const getFolderTreeViewId = (folder: Folder): string => {
	return folder.encodedSource
}

export const getFolderTreeParentId = (folder: Folder): string => {
	if (folder.dirname === '/') {
		return folderTreeId
	}
	return dirname(folder.encodedSource)
}
