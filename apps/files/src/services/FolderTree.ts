/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ContentsWithRoot } from '@nextcloud/files'

import { CancelablePromise } from 'cancelable-promise'
import { davRemoteURL } from '@nextcloud/files'
import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { dirname, encodePath, joinPaths } from '@nextcloud/paths'
import { getCanonicalLocale, getLanguage } from '@nextcloud/l10n'

import { getContents as getFiles } from './Files.ts'

// eslint-disable-next-line no-use-before-define
type Tree = TreeNodeData[]

interface TreeNodeData {
	id: number,
	basename: string,
	displayName?: string,
	children: Tree,
}

export interface TreeNode {
	source: string,
	encodedSource: string,
	path: string,
	fileid: number,
	basename: string,
	displayName?: string,
}

export const folderTreeId = 'folders'

export const sourceRoot = `${davRemoteURL}/files/${getCurrentUser()?.uid}`

const collator = Intl.Collator(
	[getLanguage(), getCanonicalLocale()],
	{
		numeric: true,
		usage: 'sort',
	},
)

const compareNodes = (a: TreeNodeData, b: TreeNodeData) => collator.compare(a.displayName ?? a.basename, b.displayName ?? b.basename)

const getTreeNodes = (tree: Tree, currentPath: string = '/', nodes: TreeNode[] = []): TreeNode[] => {
	const sortedTree = tree.toSorted(compareNodes)
	for (const { id, basename, displayName, children } of sortedTree) {
		const path = joinPaths(currentPath, basename)
		const source = `${sourceRoot}${path}`
		const node: TreeNode = {
			source,
			encodedSource: encodeSource(source),
			path,
			fileid: id,
			basename,
		}
		if (displayName) {
			node.displayName = displayName
		}
		nodes.push(node)
		if (children.length > 0) {
			getTreeNodes(children, path, nodes)
		}
	}
	return nodes
}

export const getFolderTreeNodes = async (path: string = '/', depth: number = 1): Promise<TreeNode[]> => {
	const { data: tree } = await axios.get<Tree>(generateOcsUrl('/apps/files/api/v1/folder-tree'), {
		params: new URLSearchParams({ path, depth: String(depth) }),
	})
	const nodes = getTreeNodes(tree, path)
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
