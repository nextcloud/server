/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ContentsWithRoot } from '@nextcloud/files'

import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { getRemoteURL } from '@nextcloud/files/dav'
import { getCanonicalLocale, getLanguage } from '@nextcloud/l10n'
import { dirname, encodePath, join } from '@nextcloud/paths'
import { generateOcsUrl } from '@nextcloud/router'
import { getContents as getFiles } from './Files.ts'

type Tree = TreeNodeData[]

interface TreeNodeData {
	id: number
	basename: string
	displayName?: string
	children: Tree
}

export interface TreeNode {
	source: string
	encodedSource: string
	path: string
	fileid: number
	basename: string
	displayName?: string
}

export const folderTreeId = 'folders'

export const sourceRoot = `${getRemoteURL()}/files/${getCurrentUser()?.uid}`

const collator = Intl.Collator(
	[getLanguage(), getCanonicalLocale()],
	{
		numeric: true,
		usage: 'sort',
	},
)

const compareNodes = (a: TreeNodeData, b: TreeNodeData) => collator.compare(a.displayName ?? a.basename, b.displayName ?? b.basename)

/**
 * Get all tree nodes recursively
 *
 * @param tree - The tree to process
 * @param currentPath - The current path
 * @param nodes - The nodes collected so far
 */
function getTreeNodes(tree: Tree, currentPath: string = '/', nodes: TreeNode[] = []): TreeNode[] {
	const sortedTree = tree.toSorted(compareNodes)
	for (const { id, basename, displayName, children } of sortedTree) {
		const path = join(currentPath, basename)
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

/**
 * Get folder tree nodes
 *
 * @param path - The path to get the tree from
 * @param depth - The depth to fetch
 */
export async function getFolderTreeNodes(path: string = '/', depth: number = 1): Promise<TreeNode[]> {
	const { data: tree } = await axios.get<Tree>(generateOcsUrl('/apps/files/api/v1/folder-tree'), {
		params: new URLSearchParams({ path, depth: String(depth) }),
	})
	const nodes = getTreeNodes(tree, path)
	return nodes
}

export const getContents = (path: string, options: { signal: AbortSignal }): Promise<ContentsWithRoot> => getFiles(path, options)

/**
 * Encode source URL
 *
 * @param source - The source URL
 */
export function encodeSource(source: string): string {
	const { origin } = new URL(source)
	return origin + encodePath(source.slice(origin.length))
}

/**
 * Get parent source URL
 *
 * @param source - The source URL
 */
export function getSourceParent(source: string): string {
	const parent = dirname(source)
	if (parent === sourceRoot) {
		return folderTreeId
	}
	return encodeSource(parent)
}
