/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { File } from '@nextcloud/files'
import { afterAll, beforeEach, describe, expect, it, vi } from 'vitest'
import { deleted, deletedBy, originalLocation } from './columns.ts'
import { trashbinView } from './trashbinView.ts'
import * as ncAuth from '@nextcloud/auth'

vi.mock('@nextcloud/l10n', async (originalModule) => ({
	...(await originalModule()),
	getLanguage: () => 'en',
	getCanonicalLocale: () => 'en-US',
}))

describe('files_trashbin: file list columns', () => {

	describe('column: original location', () => {
		it('has id set', () => {
			expect(originalLocation.id).toBe('files_trashbin--original-location')
		})

		it('has title set', () => {
			expect(originalLocation.title).toBe('Original location')
		})

		it('correctly sorts nodes by original location', () => {
			const nodeA = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/a.txt', mime: 'text/plain', attributes: { 'trashbin-original-location': 'z-folder/a.txt' } })
			const nodeB = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/b.txt', mime: 'text/plain', attributes: { 'trashbin-original-location': 'folder/b.txt' } })

			expect(originalLocation.sort).toBeTypeOf('function')
			expect(originalLocation.sort!(nodeA, nodeB)).toBeGreaterThan(0)
			expect(originalLocation.sort!(nodeB, nodeA)).toBeLessThan(0)
		})

		it('renders a node with original location', () => {
			const node = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/a.txt', mime: 'text/plain', attributes: { 'trashbin-original-location': 'folder/a.txt' } })
			const el: HTMLElement = originalLocation.render(node, trashbinView)
			expect(el).toBeInstanceOf(HTMLElement)
			expect(el.textContent).toBe('folder')
			expect(el.title).toBe('folder')
		})

		it('renders a node when original location is missing', () => {
			const node = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/a.txt', mime: 'text/plain' })
			const el: HTMLElement = originalLocation.render(node, trashbinView)
			expect(el).toBeInstanceOf(HTMLElement)
			expect(el.textContent).toBe('Unknown')
			expect(el.title).toBe('Unknown')
		})

		it('renders a node when original location is the root', () => {
			const node = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/a.txt', mime: 'text/plain', attributes: { 'trashbin-original-location': 'a.txt' } })
			const el: HTMLElement = originalLocation.render(node, trashbinView)
			expect(el).toBeInstanceOf(HTMLElement)
			expect(el.textContent).toBe('All files')
			expect(el.title).toBe('All files')
		})
	})

	describe('column: deleted time', () => {
		it('has id set', () => {
			expect(deleted.id).toBe('files_trashbin--deleted')
		})

		it('has title set', () => {
			expect(deleted.title).toBe('Deleted')
		})

		it('correctly sorts nodes by deleted time', () => {
			const nodeA = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/a.txt', mime: 'text/plain', attributes: { 'trashbin-deletion-time': 1741684522 } })
			const nodeB = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/b.txt', mime: 'text/plain', attributes: { 'trashbin-deletion-time': 1741684422 } })

			expect(deleted.sort).toBeTypeOf('function')
			expect(deleted.sort!(nodeA, nodeB)).toBeLessThan(0)
			expect(deleted.sort!(nodeB, nodeA)).toBeGreaterThan(0)
		})

		it('correctly sorts nodes by deleted time and falls back to mtime', () => {
			const nodeA = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/a.txt', mime: 'text/plain', attributes: { 'trashbin-deletion-time': 1741684522 } })
			const nodeB = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/b.txt', mime: 'text/plain', mtime: new Date(1741684422000) })

			expect(deleted.sort).toBeTypeOf('function')
			expect(deleted.sort!(nodeA, nodeB)).toBeLessThan(0)
			expect(deleted.sort!(nodeB, nodeA)).toBeGreaterThan(0)
		})

		it('correctly sorts nodes even if no deletion date is provided', () => {
			const nodeA = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/a.txt', mime: 'text/plain' })
			const nodeB = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/b.txt', mime: 'text/plain', mtime: new Date(1741684422000) })

			expect(deleted.sort).toBeTypeOf('function')
			expect(deleted.sort!(nodeA, nodeB)).toBeGreaterThan(0)
			expect(deleted.sort!(nodeB, nodeA)).toBeLessThan(0)
		})

		describe('rendering', () => {
			afterAll(() => {
				vi.useRealTimers()
			})

			beforeEach(() => {
				vi.useFakeTimers({ now: 1741684582000 })
			})

			it('renders a node with deletion date', () => {
				const node = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/a.txt', mime: 'text/plain', attributes: { 'trashbin-deletion-time': (Date.now() / 1000) - 120 } })
				const el: HTMLElement = deleted.render(node, trashbinView)
				expect(el).toBeInstanceOf(HTMLElement)
				expect(el.textContent).toBe('2 minutes ago')
				expect(el.title).toBe('March 11, 2025 at 9:14 AM')
			})

			it('renders a node when deletion date is missing and falls back to mtime', () => {
				const node = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/a.txt', mime: 'text/plain', mtime: new Date(Date.now() - 60000) })
				const el: HTMLElement = deleted.render(node, trashbinView)
				expect(el).toBeInstanceOf(HTMLElement)
				expect(el.textContent).toBe('1 minute ago')
				expect(el.title).toBe('March 11, 2025 at 9:15 AM')
			})

			it('renders a node when deletion date is missing', () => {
				const node = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/a.txt', mime: 'text/plain' })
				const el: HTMLElement = deleted.render(node, trashbinView)
				expect(el).toBeInstanceOf(HTMLElement)
				expect(el.textContent).toBe('A long time ago')
			})
		})

		describe('column: deleted by', () => {
			it('has id set', () => {
				expect(deletedBy.id).toBe('files_trashbin--deleted-by')
			})

			it('has title set', () => {
				expect(deletedBy.title).toBe('Deleted by')
			})

			it('correctly sorts nodes by user-id of deleting user', () => {
				const nodeA = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/a.txt', mime: 'text/plain', attributes: { 'trashbin-deleted-by-id': 'zzz' } })
				const nodeB = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/b.txt', mime: 'text/plain', attributes: { 'trashbin-deleted-by-id': 'aaa' } })

				expect(deletedBy.sort).toBeTypeOf('function')
				expect(deletedBy.sort!(nodeA, nodeB)).toBeGreaterThan(0)
				expect(deletedBy.sort!(nodeB, nodeA)).toBeLessThan(0)
			})

			it('correctly sorts nodes by display name of deleting user', () => {
				const nodeA = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/a.txt', mime: 'text/plain', attributes: { 'trashbin-deleted-by-display-name': 'zzz' } })
				const nodeB = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/b.txt', mime: 'text/plain', attributes: { 'trashbin-deleted-by-display-name': 'aaa' } })

				expect(deletedBy.sort).toBeTypeOf('function')
				expect(deletedBy.sort!(nodeA, nodeB)).toBeGreaterThan(0)
				expect(deletedBy.sort!(nodeB, nodeA)).toBeLessThan(0)
			})

			it('correctly sorts nodes by display name of deleting user before user id', () => {
				const nodeA = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/a.txt', mime: 'text/plain', attributes: { 'trashbin-deleted-by-display-name': '000', 'trashbin-deleted-by-id': 'zzz' } })
				const nodeB = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/b.txt', mime: 'text/plain', attributes: { 'trashbin-deleted-by-display-name': 'aaa', 'trashbin-deleted-by-id': '999' } })

				expect(deletedBy.sort).toBeTypeOf('function')
				expect(deletedBy.sort!(nodeA, nodeB)).toBeLessThan(0)
				expect(deletedBy.sort!(nodeB, nodeA)).toBeGreaterThan(0)
			})

			it('correctly sorts nodes even when one is missing', () => {
				const nodeA = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/a.txt', mime: 'text/plain', attributes: { 'trashbin-deleted-by-id': 'aaa' } })
				const nodeB = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/a.txt', mime: 'text/plain', attributes: { 'trashbin-deleted-by-id': 'zzz' } })
				const nodeC = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/b.txt', mime: 'text/plain' })

				expect(deletedBy.sort).toBeTypeOf('function')
				// aaa is less then "Unknown"
				expect(deletedBy.sort!(nodeA, nodeC)).toBeLessThan(0)
				// zzz is greater than "Unknown"
				expect(deletedBy.sort!(nodeB, nodeC)).toBeGreaterThan(0)
			})

			it('renders a node with deleting user', () => {
				const node = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/a.txt', mime: 'text/plain', attributes: { 'trashbin-deleted-by-id': 'user-id' } })
				const el: HTMLElement = deletedBy.render(node, trashbinView)
				expect(el).toBeInstanceOf(HTMLElement)
				expect(el.textContent).toMatch(/\suser-id\s/)
			})

			it('renders a node with deleting user display name', () => {
				const node = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/a.txt', mime: 'text/plain', attributes: { 'trashbin-deleted-by-display-name': 'user-name', 'trashbin-deleted-by-id': 'user-id' } })
				const el: HTMLElement = deletedBy.render(node, trashbinView)
				expect(el).toBeInstanceOf(HTMLElement)
				expect(el.textContent).toMatch(/\suser-name\s/)
			})

			it('renders a node even when information is missing', () => {
				const node = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/a.txt', mime: 'text/plain' })
				const el: HTMLElement = deletedBy.render(node, trashbinView)
				expect(el).toBeInstanceOf(HTMLElement)
				expect(el.textContent).toBe('Unknown')
			})

			it('renders a node when current user is the deleting user', () => {
				vi.spyOn(ncAuth, 'getCurrentUser').mockImplementationOnce(() => ({
					uid: 'user-id',
					displayName: 'user-display-name',
					isAdmin: false,
				}))

				const node = new File({ owner: 'test', source: 'https://example.com/remote.php/dav/files/test/a.txt', mime: 'text/plain', attributes: { 'trashbin-deleted-by-id': 'user-id' } })
				const el: HTMLElement = deletedBy.render(node, trashbinView)
				expect(el).toBeInstanceOf(HTMLElement)
				expect(el.textContent).toBe('You')
			})
		})

	})

})
