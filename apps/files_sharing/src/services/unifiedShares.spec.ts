/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, test, vi } from 'vitest'
import { SOURCE_TYPE_NODE } from '../lib/unifiedSharingConstants.ts'
import { deleteShare, getSharesForNode, removeRecipient } from './unifiedShares.ts'

const axios = vi.hoisted(() => ({ get: vi.fn(), delete: vi.fn() }))
vi.mock('@nextcloud/axios', () => ({ default: axios }))

const capabilities = vi.hoisted(() => ({ value: {} as unknown }))
vi.mock('@nextcloud/capabilities', () => ({ getCapabilities: () => capabilities.value }))

vi.mock('@nextcloud/router', () => ({
	generateOcsUrl: (path: string, params?: Record<string, string>) => 'ocs/' + path.replace(/\{(\w+)\}/g, (_, k) => params?.[k] ?? ''),
}))

vi.mock('./logger.ts', () => ({ default: { warn: vi.fn(), error: vi.fn(), debug: vi.fn() } }))

const ocs = (data: unknown[]) => ({ data: { ocs: { data } } })
const node = { fileid: 42 } as never

beforeEach(() => {
	vi.resetAllMocks()
	capabilities.value = { sharing: { source_types: [{ class: SOURCE_TYPE_NODE }] } }
})

describe('getSharesForNode', () => {
	test('returns [] and does not call the API when the node source type is unavailable', async () => {
		capabilities.value = { sharing: { source_types: [] } }
		expect(await getSharesForNode(node)).toEqual([])
		expect(axios.get).not.toHaveBeenCalled()
	})

	test('unwraps ocs.data and filters by node', async () => {
		axios.get.mockResolvedValueOnce(ocs([{ id: '1' }, { id: '2' }]))
		const shares = await getSharesForNode(node)
		expect(shares).toEqual([{ id: '1' }, { id: '2' }])
		expect(axios.get).toHaveBeenCalledTimes(1)
		expect(axios.get.mock.calls[0][1].params).toMatchObject({
			filterSourceTypeClass: SOURCE_TYPE_NODE,
			filterSourceTypeValue: '42',
			filterState: 'active',
			limit: 100,
		})
	})

	test('walks all pages until a short page', async () => {
		const fullPage = Array.from({ length: 100 }, (_, i) => ({ id: String(i) }))
		axios.get
			.mockResolvedValueOnce(ocs(fullPage))
			.mockResolvedValueOnce(ocs([{ id: '100' }, { id: '101' }]))
		const shares = await getSharesForNode(node)
		expect(shares).toHaveLength(102)
		expect(axios.get).toHaveBeenCalledTimes(2)
		// Second page requests the id after the last of the first page.
		expect(axios.get.mock.calls[1][1].params.lastShareID).toBe('99')
	})
})

describe('deleteShare', () => {
	test('DELETEs the share by id', async () => {
		await deleteShare('7')
		expect(axios.delete).toHaveBeenCalledWith('ocs//apps/sharing/api/v1/share/7')
	})
})

describe('removeRecipient', () => {
	test('DELETEs the recipient with the class/value/instance body', async () => {
		await removeRecipient('7', 'RecipClass', 'bob', null)
		expect(axios.delete).toHaveBeenCalledWith('ocs//apps/sharing/api/v1/share/7/recipient', {
			data: { class: 'RecipClass', value: 'bob', instance: null },
		})
	})
})
