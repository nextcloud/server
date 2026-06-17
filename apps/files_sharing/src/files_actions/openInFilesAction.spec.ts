/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder, IView } from '@nextcloud/files'

import { DefaultType, File, Permission } from '@nextcloud/files'
import { describe, expect, test, vi } from 'vitest'
import { deletedSharesViewId, pendingSharesViewId, sharedWithOthersViewId, sharedWithYouViewId, sharesViewId, sharingByLinksViewId } from '../files_views/shares.ts'
import { action } from './openInFilesAction.ts'

import '../main.ts'

const view = {
	id: 'files',
	name: 'Files',
} as IView

const validViews = [
	sharesViewId,
	sharedWithYouViewId,
	sharedWithOthersViewId,
	sharingByLinksViewId,
].map((id) => ({ id, name: id })) as IView[]

const invalidViews = [
	deletedSharesViewId,
	pendingSharesViewId,
].map((id) => ({ id, name: id })) as IView[]

describe('Open in files action conditions tests', () => {
	test('Default values', () => {
		expect(action.id).toBe('files_sharing:open-in-files')
		expect(action.displayName({
			nodes: [],
			view: validViews[0]!,
			folder: {} as IFolder,
			contents: [],
		})).toBe('Open in Files')
		expect(action.iconSvgInline({
			nodes: [],
			view: validViews[0]!,
			folder: {} as IFolder,
			contents: [],
		})).toBe('')
		expect(action.default).toBe(DefaultType.HIDDEN)
		expect(action.order).toBe(-1000)
		expect(action.inline).toBeUndefined()
	})
})

describe('Open in files action enabled tests', () => {
	test('Enabled with on valid view', () => {
		validViews.forEach((view) => {
			expect(action.enabled).toBeDefined()
			expect(action.enabled!({
				nodes: [],
				view,
				folder: {} as IFolder,
				contents: [],
			})).toBe(true)
		})
	})

	test('Disabled on wrong view', () => {
		invalidViews.forEach((view) => {
			expect(action.enabled).toBeDefined()
			expect(action.enabled!({
				nodes: [],
				view,
				folder: {} as IFolder,
				contents: [],
			})).toBe(false)
		})
	})
})

describe('Open in files action execute tests', () => {
	test('Open in files', async () => {
		const goToRouteMock = vi.fn()
		// @ts-expect-error -- mocking for tests
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock } } }

		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Foo/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			root: '/files/admin',
			permissions: Permission.READ,
		})

		const exec = await action.exec({
			nodes: [file],
			view,
			folder: {} as IFolder,
			contents: [],
		})
		// Silent action
		expect(exec).toBe(null)
		expect(goToRouteMock).toBeCalledTimes(1)
		expect(goToRouteMock).toBeCalledWith(null, { fileid: '1', view: 'files' }, { dir: '/Foo', openfile: 'true' })
	})
})
