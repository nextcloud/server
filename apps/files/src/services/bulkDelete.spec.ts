/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { BulkDeleteItem } from './bulkDelete.ts'

import { expect, test, vi } from 'vitest'
import { createBDeleteBody, getBDeleteHref, isBulkDeleteItem, parseBDeleteResponse, runBulkDelete, validateBulkDeleteResponse } from './bulkDelete.ts'

function files(count: number): BulkDeleteItem[] {
	return Array.from({ length: count }, (_, index) => ({ path: `/file-${index}.txt`, fileId: index + 1 }))
}

function success(batch: BulkDeleteItem[]) {
	return { results: batch.map((file) => ({ ...file, status: 204, attempted: true })), stopped: false }
}

function multiStatus(entries: Array<[string, number]>): string {
	var responses = entries.map(([href, status]) => `<d:response><d:href>${href}</d:href><d:status>HTTP/1.1 ${status} Error</d:status></d:response>`).join('')
	return `<?xml version="1.0" encoding="UTF-8"?><d:multistatus xmlns:d="DAV:">${responses}</d:multistatus>`
}

test.each([[385, 4], [970, 10], [1100, 11]])('%i files use %i BDELETE requests', async (count, requests) => {
	var request = vi.fn(async (batch: BulkDeleteItem[]) => success(batch))
	var onDeleted = vi.fn()
	var result = await runBulkDelete(files(count), { batchSize: 100, request, onDeleted, onError: vi.fn() })
	expect(request).toHaveBeenCalledTimes(requests)
	expect(onDeleted).toHaveBeenCalledTimes(count)
	expect(result).toEqual(Array(count).fill(true))
	expect(request.mock.calls.every(([batch]) => batch.length <= 100)).toBe(true)
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

test('creates an Exchange-style DAV delete body', () => {
	var batch = [
		{ path: '/folder/café + #.txt', fileId: 1 },
		{ path: '/literal%2Fname.txt', fileId: 2 },
	]
	var body = createBDeleteBody(batch)
	expect(body).toContain('<d:delete xmlns:d="DAV:"><d:target>')
	expect(body).toContain('<d:href>folder/caf%C3%A9%20%2B%20%23.txt</d:href>')
	expect(body).toContain('<d:href>literal%252Fname.txt</d:href>')
})

test('204 means every target was deleted', () => {
	var batch = files(2)
	expect(parseBDeleteResponse(204, '', batch)).toEqual(success(batch))
})

test('207 maps the failed target and unattempted suffix', () => {
	var batch = files(3)
	var response = parseBDeleteResponse(207, multiStatus([
		[getBDeleteHref(batch[1]!), 403],
		[getBDeleteHref(batch[2]!), 424],
	]), batch)
	expect(response).toEqual({
		results: [
			{ ...batch[0]!, status: 204, attempted: true },
			{ ...batch[1]!, status: 403, attempted: true },
			{ ...batch[2]!, status: 424, attempted: false },
		],
		stopped: true,
	})
})

test('malformed or mismatched multistatus cannot remove visible nodes', () => {
	var batch = files(2)
	expect(() => parseBDeleteResponse(207, '<d:multistatus xmlns:d="DAV:"/>', batch)).toThrow()
	expect(() => parseBDeleteResponse(207, multiStatus([['other.txt', 403]]), batch)).toThrow()
	expect(() => parseBDeleteResponse(200, '', batch)).toThrow()
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
	expect(onDeleted).toHaveBeenCalledTimes(1)
	expect(onDeleted).toHaveBeenCalledWith(0)
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

test.each(['café + #.txt', '日本語.txt', 'literal%2Fname.txt', 'emoji-😀.txt'])('preserves UTF-8 path %s', (name) => {
	var file = { path: `/folder/${name}`, fileId: 1 }
	expect(isBulkDeleteItem(file)).toBe(true)
	expect(getBDeleteHref(file)).not.toContain(' ')
})

test('rejects duplicate selections before any request', async () => {
	var request = vi.fn()
	var batch = files(1)
	await runBulkDelete([batch[0]!, batch[0]!], { batchSize: 100, request, onDeleted: vi.fn(), onError: vi.fn() })
	expect(request).not.toHaveBeenCalled()
})
