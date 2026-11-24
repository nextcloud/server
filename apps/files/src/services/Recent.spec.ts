/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { getContents } from './Recent.ts'

vi.hoisted(() => {
	// Serve Nextcloud from a sub directory like a reverse proxy would
	window._oc_webroot = '/myserver'
})

const search = vi.hoisted(() => vi.fn())
vi.mock('./WebdavClient.ts', () => ({ client: { search } }))
vi.mock('@nextcloud/auth')
vi.mock('@nextcloud/capabilities')
vi.mock('@nextcloud/initial-state')

describe('Recent service', () => {
	beforeEach(() => {
		vi.resetAllMocks()
		setActivePinia(createPinia())
	})

	it('resolves search result paths on a sub directory installation', async () => {
		search.mockResolvedValueOnce({
			data: {
				results: [
					{
						filename: '/myserver/remote.php/dav/files/test/doc.txt',
						basename: 'doc.txt',
						lastmod: 'Mon, 10 Nov 2025 00:00:00 GMT',
						size: 10,
						type: 'file',
						mime: 'text/plain',
						props: {
							fileid: 42,
							permissions: 'SRGNVW',
							getcontentlength: '10',
							displayname: 'doc.txt',
							creationdate: 'Mon, 10 Nov 2025 00:00:00 GMT',
						},
					},
				],
			},
		})

		const { contents } = await getContents('/', { signal: new AbortController().signal })

		expect(contents).toHaveLength(1)
		expect(contents[0].source).toBe('http://nextcloud.local/myserver/remote.php/dav/files/test/doc.txt')
		expect(contents[0].path).toBe('/doc.txt')
	})
})
