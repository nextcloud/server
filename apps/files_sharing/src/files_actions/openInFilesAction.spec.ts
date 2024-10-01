/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { File, Permission, View, DefaultType, FileAction } from '@nextcloud/files'
import { describe, expect, test, vi } from 'vitest'
import { deletedSharesViewId, pendingSharesViewId, sharedWithOthersViewId, sharedWithYouViewId, sharesViewId, sharingByLinksViewId } from '../files_views/shares'
import { action } from './openInFilesAction'

import '../main'

const view = {
	id: 'files',
	name: 'Files',
} as View

const validViews = [
	sharesViewId,
	sharedWithYouViewId,
	sharedWithOthersViewId,
	sharingByLinksViewId,
].map(id => ({ id, name: id })) as View[]

const invalidViews = [
	deletedSharesViewId,
	pendingSharesViewId,
].map(id => ({ id, name: id })) as View[]

describe('Open in files action conditions tests', () => {
	test('Default values', () => {
		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('open-in-files')
		expect(action.displayName([], validViews[0])).toBe('Open in Files')
		expect(action.iconSvgInline([], validViews[0])).toBe('')
		expect(action.default).toBe(DefaultType.HIDDEN)
		expect(action.order).toBe(-1000)
		expect(action.inline).toBeUndefined()
	})
})

describe('Open in files action enabled tests', () => {
	test('Enabled with on valid view', () => {
		validViews.forEach(view => {
			expect(action.enabled).toBeDefined()
			expect(action.enabled!([], view)).toBe(true)
		})
	})

	test('Disabled on wrong view', () => {
		invalidViews.forEach(view => {
			expect(action.enabled).toBeDefined()
			expect(action.enabled!([], view)).toBe(false)
		})
	})
})

describe('Open in files action execute tests', () => {
	test('Open in files', async () => {
		const goToRouteMock = vi.fn()
		// @ts-expect-error We only mock what needed, we do not need Files.Router.goTo or Files.Navigation
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			root: '/files/admin',
			permissions: Permission.READ,
		})

		const exec = await action.exec(file, view, '/')
		// Silent action
		expect(exec).toBe(null)
		expect(goToRouteMock).toBeCalledTimes(1)
		expect(goToRouteMock).toBeCalledWith(null, { fileid: '1', view: 'files' }, { dir: '/Foo', openfile: 'true' })
	})
})
