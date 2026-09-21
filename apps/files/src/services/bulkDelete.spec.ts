/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { BulkDeleteItem } from './bulkDelete.ts'

import { expect, test, vi } from 'vitest'
import { isBulkDeleteItem, runBulkDelete, validateBulkDeleteResponse } from './bulkDelete.ts'

function files(count: number): BulkDeleteItem[] {
   return Array.from({ length: count }, (_, index) => ({ path: `/file-${index}.txt`, fileId: index + 1 }))
}

function success(batch: BulkDeleteItem[]) {
   return { results: batch.map((file) => ({ ...file, status: 204, attempted: true })), stopped: false }
}

test.each([[385, 4], [970, 10], [1100, 11]])('%i files use %i POSTs', async (count, requests) => {
   var request = vi.fn(async (batch: BulkDeleteItem[]) => success(batch))
   var onDeleted = vi.fn()
   var result = await runBulkDelete(files(count), { batchSize: 100, request, onDeleted, onError: vi.fn() })
   expect(request).toHaveBeenCalledTimes(requests)
   expect(onDeleted).toHaveBeenCalledTimes(count)
   expect(result).toEqual(Array(count).fill(true))
   expect(request.mock.calls.every(([batch]) => batch.length <= 100)).toBe(true)
})

test('requests can complete out of order without reordering results', async () => {
   var onDeleted = vi.fn()
   var result = await runBulkDelete(files(4), {
      batchSize: 2,
      async request(batch) {
         await new Promise((resolve) => setTimeout(resolve, batch[0]!.fileId === 1 ? 10 : 0))
         return success(batch)
      },
      onDeleted,
      onError: vi.fn(),
   })
   expect(result).toEqual([true, true, true, true])
   expect(onDeleted.mock.calls.map(([index]) => index)).toEqual([2, 3, 0, 1])
})

test('never has more than five requests in flight', async () => {
   var active = 0
   var maximum = 0
   await runBulkDelete(files(1100), {
      batchSize: 100,
      async request(batch) {
         active++
         maximum = Math.max(maximum, active)
         await new Promise((resolve) => setTimeout(resolve, 1))
         active--
         return success(batch)
      },
      onDeleted: vi.fn(),
      onError: vi.fn(),
   })
   expect(maximum).toBe(5)
})

test('partial failure emits success only for confirmed items and stops unsent batches', async () => {
   var request = vi.fn(async (batch: BulkDeleteItem[]) => ({
      results: batch.map((file, index) => ({ ...file, status: [204, 403, 424][index], attempted: index < 2 })),
      stopped: true,
   }))
   var onDeleted = vi.fn()
   var onError = vi.fn()
   var result = await runBulkDelete(files(6), { batchSize: 3, concurrency: 1, request, onDeleted, onError })
   expect(result).toEqual([true, false, false, false, false, false])
   expect(request).toHaveBeenCalledTimes(1)
   expect(onDeleted).toHaveBeenCalledExactlyOnceWith(0)
   expect(onError).toHaveBeenCalledTimes(1)
})

test('a lost response is never retried', async () => {
   var request = vi.fn(async () => { throw new Error('network timeout') })
   var onDeleted = vi.fn()
   var result = await runBulkDelete(files(200), { batchSize: 100, concurrency: 1, request, onDeleted, onError: vi.fn() })
   expect(result.every((value) => !value)).toBe(true)
   expect(request).toHaveBeenCalledTimes(1)
   expect(onDeleted).not.toHaveBeenCalled()
})

test('malformed response cannot remove visible nodes', async () => {
   var request = vi.fn(async () => ({ results: [], stopped: false }))
   var onDeleted = vi.fn()
   expect(await runBulkDelete(files(2), { batchSize: 100, request, onDeleted, onError: vi.fn() })).toEqual([false, false])
   expect(onDeleted).not.toHaveBeenCalled()
})

test('response identities and the stopped suffix are validated', () => {
   var batch = files(3)
   var response = success(batch)
   response.results[1]!.fileId = 999
   expect(() => validateBulkDeleteResponse(response, batch)).toThrow()
   response = success(batch)
   response.results[1]!.status = 403
   response.stopped = true
   expect(() => validateBulkDeleteResponse(response, batch)).toThrow()
})

test.each(['/', '../file', '/a/../b', '/a//b', '/a/./b', '/a\\b', '/a\u0000b'])('rejects unsafe path %s', (path) => {
   expect(isBulkDeleteItem({ path, fileId: 1 })).toBe(false)
})

test.each(['café + #.txt', '日本語.txt', 'literal%2Fname.txt', 'emoji-😀.txt'])('preserves UTF-8 path %s', async (name) => {
   var batch = [{ path: `/folder/${name}`, fileId: 1 }]
   var request = vi.fn(async (items: BulkDeleteItem[]) => success(items))
   expect(await runBulkDelete(batch, { batchSize: 100, request, onDeleted: vi.fn(), onError: vi.fn() })).toEqual([true])
   expect(request.mock.calls[0]![0]).toEqual(batch)
})

test('rejects duplicate selections before any request', async () => {
   var request = vi.fn()
   var batch = files(1)
   await runBulkDelete([batch[0]!, batch[0]!], { batchSize: 100, request, onDeleted: vi.fn(), onError: vi.fn() })
   expect(request).not.toHaveBeenCalled()
})
