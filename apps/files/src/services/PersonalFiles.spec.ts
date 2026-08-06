/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Folder } from '@nextcloud/files'
import { describe, expect, it, vi } from 'vitest'
import { getContents as getPersonalFiles } from './PersonalFiles.ts'

const getContents = vi.hoisted(() => vi.fn())
vi.mock('./Files.ts', () => ({ getContents }))

const fakeFolder = new Folder({
	owner: 'owner',
	source: 'https://cloud.example.com/remote.php/dav/files/owner/folder',
	root: '/',
	size: 3,
})

const fakeNodes = {
	contents: [
		{
			attributes: {
				'mount-type': '',
			},
			size: 1,
		},
		{
			attributes: {
				'mount-type': '',
			},
			size: 1,
		},
		{
			attributes: {
				'mount-type': 'group',
			},
			size: 1,
		},
	],
	folder: fakeFolder,
}

describe('Personal files service', () => {
	getContents.mockImplementationOnce(() => Promise.resolve(fakeNodes))

	it('Excludes the file size of excluded files', async () => {
		const contents = await getPersonalFiles('/', { signal: new AbortController().signal })
		expect(contents.folder.size).toBe(2)
	})
})
