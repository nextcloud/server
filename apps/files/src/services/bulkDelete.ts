/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { encodePath } from '@nextcloud/paths'

export interface BulkDeleteItem {
	path: string
	fileId: number
}

export interface BulkDeleteResult extends BulkDeleteItem {
	status: number
	attempted: boolean
}

export interface BulkDeleteResponse {
	results: BulkDeleteResult[]
	stopped: boolean
}

export interface BulkDeleteOptions {
	batchSize: number
	concurrency?: number
	request: (files: BulkDeleteItem[]) => Promise<unknown>
	onDeleted: (index: number) => void
	onError: (error: unknown) => void
}

/** Both sides validate the same raw UTF-8, user-relative paths. */
export function isBulkDeleteItem(value: BulkDeleteItem): boolean {
	return Number.isSafeInteger(value.fileId) && value.fileId > 0
		&& typeof value.path === 'string' && value.path.startsWith('/')
		&& new TextEncoder().encode(value.path).length <= 4096
		&& !/[\u0000-\u001f\u007f\\]/.test(value.path)
		&& value.path.slice(1).split('/').every((part) => part !== '' && part !== '.' && part !== '..')
}

export function getBDeleteHref(file: BulkDeleteItem): string {
	return encodePath(file.path).replace(/^\/+/, '')
}

function escapeXml(value: string): string {
	return value
		.replaceAll('&', '&amp;')
		.replaceAll('<', '&lt;')
		.replaceAll('>', '&gt;')
		.replaceAll('"', '&quot;')
		.replaceAll("'", '&apos;')
}

export function createBDeleteBody(files: BulkDeleteItem[]): string {
	const hrefs = files.map((file) => `<d:href>${escapeXml(getBDeleteHref(file))}</d:href>`).join('')
	return `<?xml version="1.0" encoding="UTF-8"?><d:delete xmlns:d="DAV:"><d:target>${hrefs}</d:target></d:delete>`
}

/**
 * Convert the Exchange-style BDELETE 204/207 response into the existing local
 * result shape used by the batching scheduler.
 */
export function parseBDeleteResponse(status: number, body: unknown, files: BulkDeleteItem[]): BulkDeleteResponse {
	if (status === 204) {
		return {
			results: files.map((file) => ({ ...file, status: 204, attempted: true })),
			stopped: false,
		}
	}
	if (status !== 207 || typeof body !== 'string') {
		throw new Error('Invalid BDELETE response; refresh the file list before retrying')
	}

	const document = new DOMParser().parseFromString(body, 'application/xml')
	if (document.querySelector('parsererror') !== null
		|| document.documentElement.namespaceURI !== 'DAV:'
		|| document.documentElement.localName !== 'multistatus') {
		throw new Error('Invalid BDELETE multistatus response')
	}

	const hrefToIndex = new Map(files.map((file, index) => [getBDeleteHref(file), index]))
	const failures = new Map<number, number>()
	for (const response of Array.from(document.documentElement.children)) {
		if (response.namespaceURI !== 'DAV:' || response.localName !== 'response') {
			throw new Error('Invalid BDELETE multistatus child')
		}
		const hrefElements = Array.from(response.children).filter((element) => element.namespaceURI === 'DAV:' && element.localName === 'href')
		const statusElements = Array.from(response.children).filter((element) => element.namespaceURI === 'DAV:' && element.localName === 'status')
		if (hrefElements.length !== 1 || statusElements.length !== 1) {
			throw new Error('Invalid BDELETE multistatus item')
		}
		const href = hrefElements[0]!.textContent ?? ''
		const match = /^HTTP\/1\.[01]\s+(\d{3})(?:\s|$)/.exec(statusElements[0]!.textContent ?? '')
		const index = hrefToIndex.get(href)
		const itemStatus = match ? Number.parseInt(match[1]!, 10) : 0
		if (index === undefined || failures.has(index) || itemStatus < 400 || itemStatus > 599) {
			throw new Error('BDELETE response does not match the requested files')
		}
		failures.set(index, itemStatus)
	}
	if (failures.size === 0) {
		throw new Error('BDELETE 207 response did not contain any failures')
	}

	const firstFailure = Math.min(...failures.keys())
	const results: BulkDeleteResult[] = files.map((file, index) => {
		if (index < firstFailure) {
			if (failures.has(index)) {
				throw new Error('Invalid BDELETE failure ordering')
			}
			return { ...file, status: 204, attempted: true }
		}
		if (index === firstFailure) {
			const itemStatus = failures.get(index)
			if (itemStatus === undefined || itemStatus === 424) {
				throw new Error('Invalid first BDELETE failure')
			}
			return { ...file, status: itemStatus, attempted: true }
		}
		const itemStatus = failures.get(index)
		if (itemStatus !== 424) {
			throw new Error('Invalid stopped BDELETE response')
		}
		return { ...file, status: 424, attempted: false }
	})

	return { results, stopped: true }
}

/** Reject malformed or mismatched local results before emitting success events. */
export function validateBulkDeleteResponse(value: unknown, files: BulkDeleteItem[]): BulkDeleteResponse {
	if (!value || typeof value !== 'object' || !('results' in value) || !Array.isArray(value.results)
		|| !('stopped' in value) || typeof value.stopped !== 'boolean' || value.results.length !== files.length) {
		throw new Error('Invalid bulk delete response; refresh the file list before retrying')
	}
	let stopped = false
	for (let index = 0; index < files.length; index++) {
		const result = value.results[index]
		const file = files[index]!
		if (!result || result.path !== file.path || result.fileId !== file.fileId
			|| !Number.isInteger(result.status) || typeof result.attempted !== 'boolean') {
			throw new Error('Bulk delete response does not match the requested files')
		}
		if (stopped) {
			if (result.attempted !== false || result.status !== 424) {
				throw new Error('Invalid bulk delete stopped-batch response')
			}
		} else {
			if (result.attempted !== true || (result.status !== 204 && (result.status < 400 || result.status > 599))) {
				throw new Error('Invalid bulk delete item status')
			}
			stopped = result.status !== 204
		}
	}
	if (value.stopped !== stopped) {
		throw new Error('Inconsistent bulk delete response')
	}
	return value as BulkDeleteResponse
}

/**
 * Bounded workers preserve input-order results even when requests finish out of
 * order. Never retry a BDELETE or fall back to DELETE after dispatching a batch.
 */
export async function runBulkDelete(files: BulkDeleteItem[], options: BulkDeleteOptions): Promise<boolean[]> {
	const results = files.map(() => false)
	const batchSize = options.batchSize
	const concurrency = options.concurrency ?? 5
	if (!Number.isInteger(batchSize) || batchSize < 1 || batchSize > 100
		|| !Number.isInteger(concurrency) || concurrency < 1 || concurrency > 5
		|| !files.every(isBulkDeleteItem)
		|| new Set(files.map((file) => file.path)).size !== files.length
		|| new Set(files.map((file) => file.fileId)).size !== files.length) {
		options.onError(new Error('Invalid bulk delete selection or limits'))
		return results
	}

	let nextIndex = 0
	let stopped = false
	async function worker(): Promise<void> {
		while (!stopped && nextIndex < files.length) {
			const start = nextIndex
			nextIndex += batchSize
			const batch = files.slice(start, start + batchSize)
			try {
				const response = validateBulkDeleteResponse(await options.request(batch), batch)
				if (response.stopped) {
					stopped = true
				}
				for (let offset = 0; offset < response.results.length; offset++) {
					if (response.results[offset]!.status === 204) {
						results[start + offset] = true
						options.onDeleted(start + offset)
					}
				}
				if (response.stopped) {
					options.onError(new Error('Bulk deletion stopped after a failed item; refresh before retrying'))
				}
			} catch (error) {
				stopped = true
				options.onError(error)
			}
		}
	}
	await Promise.all(Array.from({ length: Math.min(concurrency, Math.ceil(files.length / batchSize)) }, () => worker()))
	return results
}
