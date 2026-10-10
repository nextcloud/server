/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'
import type { OCSResponse } from '@nextcloud/typings/ocs'

import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { File, Folder } from '@nextcloud/files'
import { generateOcsUrl } from '@nextcloud/router'

/**
 * A node as returned by the nodes API, the `FilesNode` type of the files app OpenAPI spec
 */
export interface NodeData {
	id: number
	source: string
	root: string
	owner: string | null
	mime: string
	mtime: number
	crtime: number | null
	size: number
	permissions: number
	displayname: string
	type: 'file' | 'folder'
	attributes: Record<string, unknown>
}

/**
 * Maximum number of ids per request
 */
export const MAX_NODE_IDS = 100

/**
 * Get the nodes with these file ids, with the same data as a PROPFIND.
 * A file reachable at several paths is returned once per path,
 * unknown or inaccessible ids are left out.
 *
 * @param fileIds - The file ids, any number: requests are split by `MAX_NODE_IDS`
 * @param options - Options
 * @param options.dir - Only return the paths within this folder, the folder itself included
 * @param options.signal - Abort signal for the requests
 */
export async function getNodesById(fileIds: number[], options: { dir?: string, signal?: AbortSignal } = {}): Promise<INode[]> {
	const nodes: INode[] = []
	for (let i = 0; i < fileIds.length; i += MAX_NODE_IDS) {
		const { data } = await axios.get<OCSResponse<NodeData[]>>(generateOcsUrl('apps/files/api/v1/nodes'), {
			params: { ids: fileIds.slice(i, i + MAX_NODE_IDS), dir: options.dir },
			signal: options.signal,
		})
		nodes.push(...data.ocs.data.map(dataToNode))
	}
	return nodes
}

/**
 * Build a node from the nodes API data, with the attributes `resultToNode` gives a PROPFIND result
 *
 * @param data - The node data
 */
export function dataToNode(data: NodeData): INode {
	const Node = data.type === 'folder' ? Folder : File
	return new Node({
		id: data.id,
		source: data.source,
		root: data.root,
		owner: data.owner ?? getCurrentUser()!.uid,
		mime: data.mime,
		mtime: new Date(data.mtime * 1000),
		crtime: data.crtime ? new Date(data.crtime * 1000) : undefined,
		size: data.size,
		permissions: data.permissions,
		displayname: data.displayname,
		attributes: {
			...data.attributes,
			hasPreview: data.attributes['has-preview'],
		},
	})
}
