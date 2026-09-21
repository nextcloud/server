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

var view = { id: 'files', name: 'Files' } as IView
var queue = new PQueue({ concurrency: 5 })

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
   vi.mocked(getCurrentUser).mockReturnValue({ uid: 'alice' } as ReturnType<typeof getCurrentUser>)
   vi.mocked(getCapabilities).mockReturnValue({ files: { undelete: true }, dav: { bulk_delete: { version: '1.0', max_files: 100 } } })
   vi.mocked(axios.post).mockImplementation(async (_url, body) => ({
      data: { results: body.files.map((item) => ({ ...item, status: 204, attempted: true })), stopped: false },
   }))
})

test('uses one POST and only confirmed deletion events', async () => {
   var nodes = [file(1), file(2)]
   expect(await deleteNodesInBatches(nodes, view, queue)).toEqual([true, true])
   expect(axios.post).toHaveBeenCalledTimes(1)
   expect(axios.delete).not.toHaveBeenCalled()
   expect(axios.post).toHaveBeenCalledWith('http://nextcloud.local/remote.php/dav/bulk-delete', {
      files: [{ path: '/file-1.txt', fileId: 1 }, { path: '/file-2.txt', fileId: 2 }],
   }, { headers: { 'Content-Type': 'application/json; charset=utf-8' } })
   expect(emit).toHaveBeenCalledWith('files:node:deleted', nodes[0])
   expect(emit).toHaveBeenCalledWith('files:node:deleted', nodes[1])
})

test('does not use the API without the advertised capability', () => {
   vi.mocked(getCapabilities).mockReturnValue({ files: { undelete: true } })
   expect(deleteNodesInBatches([file(1), file(2)], view, queue)).toBeNull()
   expect(axios.post).not.toHaveBeenCalled()
})

test('keeps permanent deletion on the existing path', () => {
   expect(deleteNodesInBatches([file(1), file(2)], { ...view, id: 'trashbin' }, queue)).toBeNull()
   vi.mocked(getCapabilities).mockReturnValue({ files: { undelete: false }, dav: { bulk_delete: { version: '1.0', max_files: 100 } } })
   expect(deleteNodesInBatches([file(1), file(2)], view, queue)).toBeNull()
})

test('keeps mixed file-folder selections on the existing path', () => {
   var folder = new Folder({ id: 3, source: 'http://nextcloud.local/remote.php/dav/files/alice/folder', root: '/files/alice', owner: 'alice', permissions: Permission.ALL })
   expect(deleteNodesInBatches([file(1), folder], view, queue)).toBeNull()
})

test('keeps shared and external mount roots on the existing path', () => {
   for (var mountType of ['shared', 'external']) {
      var mounted = file(1)
      mounted.attributes['is-mount-root'] = true
      mounted.attributes['mount-type'] = mountType
      expect(deleteNodesInBatches([mounted, file(2)], view, queue)).toBeNull()
   }
})

test('rejects foreign origins, user roots and public share paths', () => {
   for (var source of [
      'https://other.invalid/remote.php/dav/files/alice/one',
      'http://nextcloud.local/remote.php/dav/files/bob/one',
      'http://nextcloud.local/remote.php/dav/public-files/token/one',
   ]) {
      var node = { ...file(1), type: file(1).type, permissions: Permission.ALL, fileid: 1, attributes: {}, encodedSource: source } as INode
      expect(deleteNodesInBatches([node, file(2)], view, queue)).toBeNull()
   }
})

test('does not fall back or retry after a POST timeout', async () => {
   vi.mocked(axios.post).mockRejectedValue(new Error('timeout'))
   expect(await deleteNodesInBatches([file(1), file(2)], view, queue)).toEqual([false, false])
   expect(axios.post).toHaveBeenCalledTimes(1)
   expect(axios.delete).not.toHaveBeenCalled()
   expect(emit).not.toHaveBeenCalled()
})
