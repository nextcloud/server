/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'
import type { Version } from './versions.ts'

import { File, Permission } from '@nextcloud/files'
import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('@nextcloud/auth', async (orig) => {
	// eslint-disable-next-line @typescript-eslint/consistent-type-imports -- vitest importOriginal idiom
	const actual = await orig<typeof import('@nextcloud/auth')>()
	return {
		...actual,
		getCurrentUser: () => ({ uid: 'alice', displayName: 'Alice', isAdmin: false }),
	}
})

const { versionToNode } = await import('./versions.ts')

/**
 * Build the file a version belongs to.
 */
function makeNode(): INode {
	return new File({
		id: 42,
		source: 'https://cloud.example.com/remote.php/dav/files/alice/holiday.jpg',
		root: '/files/alice',
		owner: 'alice',
		mime: 'image/jpeg',
		mtime: new Date('2025-01-22T11:20:00Z'),
	})
}

/**
 * Build a version of that file, as fetchVersions returns them.
 *
 * @param overrides - Fields to override on the version
 */
function makeVersion(overrides: Partial<Version> = {}): Version {
	return {
		fileId: '42',
		label: '',
		author: 'alice',
		authorName: 'Alice',
		filename: '/versions/alice/versions/42/1737542400',
		basename: '22 January 2025, 11:20:00',
		mime: 'image/jpeg',
		etag: 'abc123',
		size: 1024,
		type: 'file',
		mtime: 1737542400000,
		permissions: 'R',
		previewUrl: 'https://cloud.example.com/apps/files_versions/preview?file=/holiday.jpg&version=1737542400',
		url: '/remote.php/dav/versions/alice/versions/42/1737542400',
		source: 'https://cloud.example.com/remote.php/dav/versions/alice/versions/42/1737542400',
		fileVersion: '1737542400',
		...overrides,
	} as Version
}

describe('versionToNode', () => {
	let node: INode

	beforeEach(() => {
		node = makeNode()
	})

	it('points at the version content rather than at its thumbnail', () => {
		const version = versionToNode(makeVersion(), node)

		expect(version.source).toBe('https://cloud.example.com/remote.php/dav/versions/alice/versions/42/1737542400')
		// The version preview is a 250px thumbnail meant for the list, so the
		// viewer has to load the version itself
		expect(version.attributes.hasPreview).toBe(false)
	})

	it('reads as the date it was taken, not as its id on the server', () => {
		const version = versionToNode(makeVersion(), node)

		expect(version.basename).toBe('1737542400')
		expect(version.displayname).toBe('22 January 2025, 11:20:00')
	})

	it('is read-only, whatever the file it belongs to allows', () => {
		const version = versionToNode(makeVersion(), node)

		expect(version.permissions).toBe(Permission.READ)
	})

	it('takes the mime of the file when the version reports none', () => {
		// The current version comes back with an empty mime
		const version = versionToNode(makeVersion({ mime: '' }), node)

		expect(version.mime).toBe('image/jpeg')
	})
})
