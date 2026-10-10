/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import { getChildrenEtags } from './Files.ts'

const getDirectoryContents = vi.hoisted(() => vi.fn())
vi.mock('./WebdavClient.ts', () => ({ client: { getDirectoryContents } }))
vi.mock('@nextcloud/auth', async (importOriginal) => ({
	...await importOriginal(),
	getCurrentUser: () => ({ uid: 'alice' }),
}))

describe('getChildrenEtags', () => {
	beforeEach(() => {
		getDirectoryContents.mockReset()
	})

	it('only requests the file ids and etags of the children', async () => {
		getDirectoryContents.mockResolvedValue({ data: [] })

		await getChildrenEtags('/folder')

		const [path, options] = getDirectoryContents.mock.calls[0]!
		expect(path).toBe('/files/alice/folder')
		expect(options.data).toContain('<d:prop><oc:fileid /><d:getetag /></d:prop>')
		expect(options.includeSelf).toBeUndefined()
	})

	it('maps the file ids to their etags', async () => {
		getDirectoryContents.mockResolvedValue({
			data: [
				{ filename: '/files/alice/folder/a.md', etag: 'etag-a', props: { fileid: 11 } },
				{ filename: '/files/alice/folder/b.md', etag: 'etag-b', props: { fileid: '12' } },
				{ filename: '/files/alice/folder/broken', etag: null, props: {} },
			],
		})

		expect(await getChildrenEtags('/folder')).toEqual(new Map([[11, 'etag-a'], [12, 'etag-b']]))
	})
})
