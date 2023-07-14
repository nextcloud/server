/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import { action } from './openInFilesAction'
import { expect } from '@jest/globals'
import { File, Permission } from '@nextcloud/files'
import { DefaultType, FileAction } from '../../../files/src/services/FileAction'
import * as eventBus from '@nextcloud/event-bus'
import axios from '@nextcloud/axios'
import type { Navigation } from '../../../files/src/services/Navigation'
import '../main'
import { deletedSharesViewId, pendingSharesViewId, sharedWithOthersViewId, sharedWithYouViewId, sharesViewId, sharingByLinksViewId } from '../views/shares'

const view = {
	id: 'files',
	name: 'Files',
} as Navigation

const validViews = [
	sharesViewId,
	sharedWithYouViewId,
	sharedWithOthersViewId,
	sharingByLinksViewId,
].map(id => ({ id, name: id })) as Navigation[]

const invalidViews = [
	deletedSharesViewId,
	pendingSharesViewId,
].map(id => ({ id, name: id })) as Navigation[]

describe('Open in files action conditions tests', () => {
	test('Default values', () => {
		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('open-in-files')
		expect(action.displayName([], validViews[0])).toBe('Open in files')
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
		const goToRouteMock = jest.fn()
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
		expect(goToRouteMock).toBeCalledWith(null, { fileid: 1, view: 'files' }, { fileid: 1, dir: '/Foo' })
	})
})
