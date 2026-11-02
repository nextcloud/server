/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { File } from '@nextcloud/files'
import { ShareType } from '@nextcloud/sharing'
import { beforeAll, beforeEach, describe, expect, test, vi } from 'vitest'
import { acceptShare, canRejectShare, rejectShare } from './PendingSharesService.ts'
import { fetchNode } from '~/apps/files/src/services/WebdavClient.ts'

vi.mock('@nextcloud/axios')
vi.mock('~/apps/files/src/services/WebdavClient.ts', () => ({ fetchNode: vi.fn() }))

beforeAll(() => {
	(window as any)._oc_webroot = ''
})

beforeEach(() => {
	vi.restoreAllMocks()
	vi.mocked(fetchNode).mockReset()
	vi.spyOn(axios, 'post')
	vi.spyOn(axios, 'delete')
})

/**
 * Create a pending share node
 *
 * @param attributes The share attributes
 */
function createShare(attributes: Record<string, unknown>) {
	return new File({
		id: 1,
		source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
		owner: 'admin',
		mime: 'text/plain',
		root: '/files/admin',
		attributes: { 'share-id': 123, ...attributes },
	})
}

describe('Pending shares service', () => {
	test('Remote group shares can not be rejected', () => {
		expect(canRejectShare(createShare({}))).toBe(true)
		expect(canRejectShare(createShare({ remote_id: '1', share_type: ShareType.RemoteGroup }))).toBe(false)
		expect(canRejectShare(createShare({ remote_id: '1', share_type: ShareType.Remote }))).toBe(true)
	})

	test('Accept local share and fetch its mount point', async () => {
		const node = createShare({})
		vi.spyOn(axios, 'get').mockResolvedValue({ data: { ocs: { data: [{ path: '/foobar (2).txt' }] } } })
		vi.mocked(fetchNode).mockResolvedValue(node)

		expect(await acceptShare(node)).toBe(node)
		expect(axios.post).toHaveBeenCalledWith('http://nextcloud.local/ocs/v2.php/apps/files_sharing/api/v1/shares/pending/123')
		expect(axios.get).toHaveBeenCalledWith('http://nextcloud.local/ocs/v2.php/apps/files_sharing/api/v1/shares/123')
		expect(fetchNode).toHaveBeenCalledWith('/foobar (2).txt')
	})

	test('Accept remote share and fetch its mount point', async () => {
		const node = createShare({ remote: 'https://remote.example' })
		vi.spyOn(axios, 'get').mockResolvedValue({ data: { ocs: { data: { mountpoint: '/foobar.txt' } } } })

		await acceptShare(node)
		expect(axios.post).toHaveBeenCalledWith('http://nextcloud.local/ocs/v2.php/apps/files_sharing/api/v1/remote_shares/pending/123')
		expect(axios.get).toHaveBeenCalledWith('http://nextcloud.local/ocs/v2.php/apps/files_sharing/api/v1/remote_shares/123')
		expect(fetchNode).toHaveBeenCalledWith('/foobar.txt')
	})

	test('Failed accept does not fetch the node', async () => {
		vi.spyOn(axios, 'post').mockRejectedValue(new Error('Mock error'))

		await expect(acceptShare(createShare({}))).rejects.toThrow('Mock error')
		expect(fetchNode).not.toHaveBeenCalled()
	})

	test('Reject pending remote share', async () => {
		await rejectShare(createShare({ remote: 'https://remote.example', accepted: 0 }))
		expect(axios.delete).toHaveBeenCalledWith('http://nextcloud.local/ocs/v2.php/apps/files_sharing/api/v1/remote_shares/pending/123')
	})

	test('Reject local share', async () => {
		await rejectShare(createShare({}))
		expect(axios.delete).toHaveBeenCalledWith('http://nextcloud.local/ocs/v2.php/apps/files_sharing/api/v1/shares/123')
	})
})
