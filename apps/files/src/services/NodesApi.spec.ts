/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { NodeData } from './NodesApi.ts'

import axios from '@nextcloud/axios'
import { File, Folder } from '@nextcloud/files'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { dataToNode, getNodesById, MAX_NODE_IDS } from './NodesApi.ts'

vi.mock('@nextcloud/axios', () => ({ default: { get: vi.fn() } }))
vi.mock('@nextcloud/auth', async (importOriginal) => ({
	...await importOriginal(),
	getCurrentUser: () => ({ uid: 'alice' }),
}))

const data: NodeData = {
	id: 11,
	source: 'https://cloud.example.com/remote.php/dav/files/alice/Photos/cat.jpg',
	root: '/files/alice',
	owner: 'bob',
	mime: 'image/jpeg',
	mtime: 1760000000,
	crtime: null,
	size: 42,
	permissions: 27,
	displayname: 'cat.jpg',
	type: 'file',
	attributes: {
		etag: 'abc',
		'has-preview': true,
		'share-types': { 'share-type': [0] },
	},
}

describe('NodesApi', () => {
	beforeEach(() => {
		vi.mocked(axios.get).mockReset()
	})

	it('builds a node like a PROPFIND result', () => {
		const node = dataToNode(data)

		expect(node).toBeInstanceOf(File)
		expect(node.fileid).toBe(11)
		expect(node.path).toBe('/Photos/cat.jpg')
		expect(node.owner).toBe('bob')
		expect(node.mtime).toEqual(new Date(1760000000 * 1000))
		expect(node.crtime).toBeUndefined()
		expect(node.size).toBe(42)
		expect(node.permissions).toBe(27)
		expect(node.attributes).toMatchObject({
			etag: 'abc',
			hasPreview: true,
			'has-preview': true,
			'share-types': { 'share-type': [0] },
		})
	})

	it('builds folders', () => {
		expect(dataToNode({ ...data, type: 'folder', mime: 'httpd/unix-directory', owner: null })).toBeInstanceOf(Folder)
	})

	it('splits the ids in requests of at most MAX_NODE_IDS', async () => {
		vi.mocked(axios.get).mockResolvedValue({ data: { ocs: { data: [data] } } })
		const fileIds = Array.from({ length: MAX_NODE_IDS + 1 }, (_, index) => index)

		const nodes = await getNodesById(fileIds, { dir: '/Photos' })

		expect(axios.get).toHaveBeenCalledTimes(2)
		expect(vi.mocked(axios.get).mock.calls[0]![1]).toMatchObject({ params: { ids: fileIds.slice(0, MAX_NODE_IDS), dir: '/Photos' } })
		expect(vi.mocked(axios.get).mock.calls[1]![1]).toMatchObject({ params: { ids: [MAX_NODE_IDS], dir: '/Photos' } })
		expect(nodes).toHaveLength(2)
	})
})
