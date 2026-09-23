/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode, IView } from '@nextcloud/files'

import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { getCapabilities } from '@nextcloud/capabilities'
import { emit } from '@nextcloud/event-bus'
import { File, Folder, Permission } from '@nextcloud/files'
import PQueue from 'p-queue'
import { beforeEach, expect, test, vi } from 'vitest'
import { deleteNodesInBatches } from './deleteBatchUtils.ts'

vi.mock('@nextcloud/auth')
vi.mock('@nextcloud/axios')
vi.mock('@nextcloud/capabilities')
vi.mock('@nextcloud/event-bus')
vi.mock('@nextcloud/router', () => ({ generateRemoteUrl: () => 'http://nextcloud.local/remote.php/dav' }))

const view = { id: 'files', name: 'Files' } as IView
let queue: PQueue

function file(id: number, name = `file-${id}.txt`): File {
	return new File({
		id,
		source: `http://nextcloud.local/remote.php/dav/files/alice/${name}`,
		owner: 'alice',
		root: '/files/alice',
		mime: 'text/plain',
		permissions: Permission.ALL,
	})
}

beforeEach(() => {
	vi.resetAllMocks()
	queue = new PQueue({ concurrency: 5 })
	vi.mocked(getCurrentUser).mockReturnValue({ uid: 'alice' } as ReturnType<typeof getCurrentUser>)
	vi.mocked(getCapabilities).mockReturnValue({ files: { undelete: true }, dav: { bulk_delete: { version: '1.0', max_files: 100 } } })
	vi.mocked(axios.request).mockResolvedValue({ status: 204, data: '' })
})

test('uses one BDELETE request and only confirmed deletion events', async () => {
	const nodes = [file(1), file(2)]
	expect(await deleteNodesInBatches(nodes, view, queue)).toEqual([true, true])
	expect(axios.request).toHaveBeenCalledTimes(1)
	expect(axios.delete).not.toHaveBeenCalled()
	expect(axios.request).toHaveBeenCalledWith({
		method: 'BDELETE',
		url: 'http://nextcloud.local/remote.php/dav/files/alice/',
		data: '<?xml version="1.0" encoding="UTF-8"?><d:delete xmlns:d="DAV:"><d:target><d:href>file-1.txt</d:href><d:href>file-2.txt</d:href></d:target></d:delete>',
		headers: { 'Content-Type': 'application/xml; charset=utf-8' },
	})
	expect(emit).toHaveBeenCalledWith('files:node:deleted', nodes[0])
	expect(emit).toHaveBeenCalledWith('files:node:deleted', nodes[1])
})

test('does not use BDELETE without the advertised capability', () => {
	vi.mocked(getCapabilities).mockReturnValue({ files: { undelete: true } })
	expect(deleteNodesInBatches([file(1), file(2)], view, queue)).toBeNull()
	expect(axios.request).not.toHaveBeenCalled()
})

test('keeps permanent deletion on the existing path', () => {
	expect(deleteNodesInBatches([file(1), file(2)], { ...view, id: 'trashbin' }, queue)).toBeNull()
	vi.mocked(getCapabilities).mockReturnValue({ files: { undelete: false }, dav: { bulk_delete: { version: '1.0', max_files: 100 } } })
	expect(deleteNodesInBatches([file(1), file(2)], view, queue)).toBeNull()
})

test('keeps mixed file-folder selections on the existing path', () => {
	const folder = new Folder({ id: 3, source: 'http://nextcloud.local/remote.php/dav/files/alice/folder', root: '/files/alice', owner: 'alice', permissions: Permission.ALL })
	expect(deleteNodesInBatches([file(1), folder], view, queue)).toBeNull()
})

test('keeps shared and external mount roots on the existing path', () => {
	for (const mountType of ['shared', 'external']) {
		const mounted = file(1)
		mounted.attributes['is-mount-root'] = true
		mounted.attributes['mount-type'] = mountType
		expect(deleteNodesInBatches([mounted, file(2)], view, queue)).toBeNull()
	}
})

test('rejects foreign origins, user roots and public share paths', () => {
	for (const source of [
		'https://other.invalid/remote.php/dav/files/alice/one',
		'http://nextcloud.local/remote.php/dav/files/bob/one',
		'http://nextcloud.local/remote.php/dav/public-files/token/one',
	]) {
		const node = { ...file(1), type: file(1).type, permissions: Permission.ALL, fileid: 1, attributes: {}, encodedSource: source } as INode
		expect(deleteNodesInBatches([node, file(2)], view, queue)).toBeNull()
	}
})

test('does not retry or fall back after a BDELETE timeout', async () => {
	vi.mocked(axios.request).mockRejectedValue(new Error('timeout'))
	expect(await deleteNodesInBatches([file(1), file(2)], view, queue)).toEqual([false, false])
	expect(axios.request).toHaveBeenCalledTimes(1)
	expect(axios.delete).not.toHaveBeenCalled()
	expect(emit).not.toHaveBeenCalled()
})
