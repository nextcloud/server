/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/viewer-page.ts'

// The viewer no longer sorts the list itself; it follows the order the Files
// app provides. These guard nextcloud/viewer#2596 (underscore ordering) and
// nextcloud/viewer#3027 (respect the active sort order).
test.describe('Viewer follows the files list order', () => {
	test('steps through underscore-suffixed names in list order', async ({ filesListPage, uploadMedia, openFile, viewerPage }) => {
		// Files app natural order is name.jpg, name_1.jpg, name_2.jpg, name_3.jpg.
		const names = ['name.jpg', 'name_1.jpg', 'name_2.jpg', 'name_3.jpg']
		for (const name of names) {
			await uploadMedia('image1.jpg', name, 'image/jpeg')
		}
		await filesListPage.open()

		await openFile('name.jpg')
		await viewerPage.waitForOpen()
		expect(await viewerPage.currentName()).toBe('name.jpg')

		for (const name of names.slice(1)) {
			await viewerPage.next()
			await viewerPage.waitForOpen()
			expect(await viewerPage.currentName()).toBe(name)
		}
	})

	test('steps through a folder sorted by modification time, not by name', async ({ filesListPage, uploadMedia, openFile, viewerPage }) => {
		// Names ascend a, b, c while the modification times do not, so a
		// viewer ordering the list itself by name shows a different file
		const day = 24 * 60 * 60
		const now = Date.now() / 1000
		await uploadMedia('image1.jpg', 'a.jpg', 'image/jpeg', now - day)
		await uploadMedia('image1.jpg', 'b.jpg', 'image/jpeg', now - day * 3)
		await uploadMedia('image1.jpg', 'c.jpg', 'image/jpeg', now - day * 2)
		await filesListPage.open()

		await filesListPage.sortByColumn('Modified')
		await expect(filesListPage.getColumnHeader('Modified')).toHaveAttribute('aria-sort', 'ascending')

		// Whatever the list shows is what the viewer has to follow, so read
		// the order rather than assuming which end welcome.txt lands on
		const listed = (await filesListPage.getRowNames()).filter((name) => name.endsWith('.jpg'))
		// The sort has to disagree with the name order, or this cannot tell
		// a viewer following the list from one sorting by name itself
		expect(listed).not.toEqual([...listed].sort())

		await openFile(listed[0]!)
		await viewerPage.waitForOpen()
		expect(await viewerPage.currentName()).toBe(listed[0])

		for (const name of listed.slice(1)) {
			await viewerPage.next()
			await viewerPage.waitForOpen()
			expect(await viewerPage.currentName()).toBe(name)
		}
	})

	test('steps backwards through a descending sort', async ({ filesListPage, uploadMedia, openFile, viewerPage }) => {
		for (const name of ['a.jpg', 'b.jpg', 'c.jpg']) {
			await uploadMedia('image1.jpg', name, 'image/jpeg')
		}
		await filesListPage.open()

		// Reversing the name order is the same list read the other way, so
		// a viewer sorting by name on its own walks it backwards
		await filesListPage.sortByColumn('Name')
		await expect(filesListPage.getColumnHeader('Name')).toHaveAttribute('aria-sort', 'descending')

		const listed = (await filesListPage.getRowNames()).filter((name) => name.endsWith('.jpg'))
		expect(listed).toEqual(['c.jpg', 'b.jpg', 'a.jpg'])

		await openFile(listed[0]!)
		await viewerPage.waitForOpen()

		for (const name of listed.slice(1)) {
			await viewerPage.next()
			await viewerPage.waitForOpen()
			expect(await viewerPage.currentName()).toBe(name)
		}

		// And back up the same way
		for (const name of [...listed].reverse().slice(1)) {
			await viewerPage.previous()
			await viewerPage.waitForOpen()
			expect(await viewerPage.currentName()).toBe(name)
		}
	})
})
