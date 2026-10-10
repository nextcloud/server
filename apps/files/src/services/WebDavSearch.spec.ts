/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRemoteURL } from '@nextcloud/files/dav'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { MAX_SEARCH_FILE_IDS, searchNodesById } from './WebDavSearch.ts'

const search = vi.hoisted(() => vi.fn())
vi.mock('./WebdavClient.ts', () => ({ client: { search } }))
vi.mock('@nextcloud/auth', async (importOriginal) => ({
	...await importOriginal(),
	getCurrentUser: () => ({ uid: 'alice' }),
}))

/**
 * A search result as returned by the webdav client, with an absolute href
 *
 * @param fileid - The file id
 * @param path - The path within the user files
 */
function result(fileid: number, path: string) {
	const filename = `${new URL(getRemoteURL()).pathname}/files/alice${path}`
	return {
		filename,
		basename: path.split('/').pop(),
		lastmod: 'Fri, 10 Oct 2026 10:00:00 GMT',
		size: 1,
		type: 'file',
		mime: 'text/plain',
		etag: `etag-${fileid}`,
		props: { fileid, permissions: 'RGDNVW' },
	}
}

describe('searchNodesById', () => {
	beforeEach(() => {
		search.mockReset()
	})

	it('searches the given file ids within the directory', async () => {
		search.mockResolvedValue({ data: { results: [] } })

		await searchNodesById([11, 20], { dir: 'folder' })

		const body: string = search.mock.calls[0]![1].data
		expect(body).toContain('<d:href>/files/alice/folder</d:href>')
		expect(body).toContain('<d:or><d:eq><d:prop><oc:fileid/></d:prop><d:literal>11</d:literal></d:eq><d:eq><d:prop><oc:fileid/></d:prop><d:literal>20</d:literal></d:eq></d:or>')
		expect(body).toContain('<d:nresults>2</d:nresults>')
	})

	it('builds the same sources as folder listings', async () => {
		search.mockResolvedValue({ data: { results: [result(11, '/folder/report.md')] } })

		const [node] = await searchNodesById([11], { dir: '/folder' })

		expect(node!.source).toBe(`${getRemoteURL()}/files/alice/folder/report.md`)
		expect(node!.fileid).toBe(11)
		expect(node!.path).toBe('/folder/report.md')
	})

	it('does not send a request without file ids', async () => {
		expect(await searchNodesById([], { dir: '/' })).toEqual([])
		expect(search).not.toHaveBeenCalled()
	})

	it('refuses more file ids than the search backend accepts', async () => {
		const fileIds = Array.from({ length: MAX_SEARCH_FILE_IDS + 1 }, (_, i) => i + 1)
		await expect(searchNodesById(fileIds, { dir: '/' })).rejects.toThrow()
		expect(search).not.toHaveBeenCalled()
	})
})
