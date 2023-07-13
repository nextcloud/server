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
import { expect } from '@jest/globals'
import axios from '@nextcloud/axios'
import { Type } from '@nextcloud/sharing'
import * as auth from '@nextcloud/auth'

import { getContents, type OCSResponse } from './SharingService'
import { File, Folder } from '@nextcloud/files'
import logger from './logger'

global.window.OC = {
	TAG_FAVORITE: '_$!<Favorite>!$_',
}

describe('SharingService methods definitions', () => {
	beforeAll(() => {
		jest.spyOn(axios, 'get').mockImplementation(async (): Promise<any> => {
			return {
				data: {
					ocs: {
						meta: {
							status: 'ok',
							statuscode: 200,
							message: 'OK',
						},
						data: [],
					},
				} as OCSResponse<any>,
			}
		})
	})

	afterAll(() => {
		jest.restoreAllMocks()
	})

	test('Shared with you', async () => {
		await getContents(true, false, false, false, [])

		expect(axios.get).toHaveBeenCalledTimes(2)
		expect(axios.get).toHaveBeenNthCalledWith(1, 'http://localhost/ocs/v2.php/apps/files_sharing/api/v1/shares', {
			headers: {
				'Content-Type': 'application/json',
			},
			params: {
				shared_with_me: true,
				include_tags: true,
			},
		})
		expect(axios.get).toHaveBeenNthCalledWith(2, 'http://localhost/ocs/v2.php/apps/files_sharing/api/v1/remote_shares', {
			headers: {
				'Content-Type': 'application/json',
			},
			params: {
				include_tags: true,
			},
		})
	})

	test('Shared with others', async () => {
		await getContents(false, true, false, false, [])

		expect(axios.get).toHaveBeenCalledTimes(1)
		expect(axios.get).toHaveBeenCalledWith('http://localhost/ocs/v2.php/apps/files_sharing/api/v1/shares', {
			headers: {
				'Content-Type': 'application/json',
			},
			params: {
				shared_with_me: false,
				include_tags: true,
			},
		})
	})

	test('Pending shares', async () => {
		await getContents(false, false, true, false, [])

		expect(axios.get).toHaveBeenCalledTimes(2)
		expect(axios.get).toHaveBeenNthCalledWith(1, 'http://localhost/ocs/v2.php/apps/files_sharing/api/v1/shares/pending', {
			headers: {
				'Content-Type': 'application/json',
			},
			params: {
				include_tags: true,
			},
		})
		expect(axios.get).toHaveBeenNthCalledWith(2, 'http://localhost/ocs/v2.php/apps/files_sharing/api/v1/remote_shares/pending', {
			headers: {
				'Content-Type': 'application/json',
			},
			params: {
				include_tags: true,
			},
		})
	})

	test('Deleted shares', async () => {
		await getContents(false, true, false, false, [])

		expect(axios.get).toHaveBeenCalledTimes(1)
		expect(axios.get).toHaveBeenCalledWith('http://localhost/ocs/v2.php/apps/files_sharing/api/v1/shares', {
			headers: {
				'Content-Type': 'application/json',
			},
			params: {
				shared_with_me: false,
				include_tags: true,
			},
		})
	})

	test('Unknown owner', async () => {
		jest.spyOn(auth, 'getCurrentUser').mockReturnValue(null)
		const results = await getContents(false, true, false, false, [])

		expect(results.folder.owner).toEqual(null)
	})
})

describe('SharingService filtering', () => {
	beforeAll(() => {
		jest.spyOn(axios, 'get').mockImplementation(async (): Promise<any> => {
			return {
				data: {
					ocs: {
						meta: {
							status: 'ok',
							statuscode: 200,
							message: 'OK',
						},
						data: [
							{
								id: '62',
								share_type: Type.SHARE_TYPE_USER,
								uid_owner: 'test',
								displayname_owner: 'test',
								permissions: 31,
								stime: 1688666292,
								expiration: '2023-07-13 00:00:00',
								token: null,
								path: '/Collaborators',
								item_type: 'folder',
								item_permissions: 31,
								mimetype: 'httpd/unix-directory',
								storage: 224,
								item_source: 419413,
								file_source: 419413,
								file_parent: 419336,
								file_target: '/Collaborators',
								item_size: 41434,
								item_mtime: 1688662980,
							},
						],
					},
				},
			}
		})
	})

	afterAll(() => {
		jest.restoreAllMocks()
	})

	test('Shared with others filtering', async () => {
		const shares = await getContents(false, true, false, false, [Type.SHARE_TYPE_USER])

		expect(axios.get).toHaveBeenCalledTimes(1)
		expect(shares.contents).toHaveLength(1)
		expect(shares.contents[0].fileid).toBe(419413)
		expect(shares.contents[0]).toBeInstanceOf(Folder)
	})

	test('Shared with others filtering empty', async () => {
		const shares = await getContents(false, true, false, false, [Type.SHARE_TYPE_LINK])

		expect(axios.get).toHaveBeenCalledTimes(1)
		expect(shares.contents).toHaveLength(0)
	})
})

describe('SharingService share to Node mapping', () => {
	const shareFile = {
		id: '66',
		share_type: 0,
		uid_owner: 'test',
		displayname_owner: 'test',
		permissions: 19,
		can_edit: true,
		can_delete: true,
		stime: 1688721609,
		parent: null,
		expiration: '2023-07-14 00:00:00',
		token: null,
		uid_file_owner: 'test',
		note: '',
		label: null,
		displayname_file_owner: 'test',
		path: '/document.md',
		item_type: 'file',
		item_permissions: 27,
		mimetype: 'text/markdown',
		has_preview: true,
		storage_id: 'home::test',
		storage: 224,
		item_source: 530936,
		file_source: 530936,
		file_parent: 419336,
		file_target: '/document.md',
		item_size: 123,
		item_mtime: 1688721600,
		share_with: 'user00',
		share_with_displayname: 'User00',
		share_with_displayname_unique: 'user00@domain.com',
		status: {
			status: 'away',
			message: null,
			icon: null,
			clearAt: null,
		},
		mail_send: 0,
		hide_download: 0,
		attributes: null,
		tags: [],
	}

	const shareFolder = {
		id: '67',
		share_type: 0,
		uid_owner: 'test',
		displayname_owner: 'test',
		permissions: 31,
		can_edit: true,
		can_delete: true,
		stime: 1688721629,
		parent: null,
		expiration: '2023-07-14 00:00:00',
		token: null,
		uid_file_owner: 'test',
		note: '',
		label: null,
		displayname_file_owner: 'test',
		path: '/Folder',
		item_type: 'folder',
		item_permissions: 31,
		mimetype: 'httpd/unix-directory',
		has_preview: false,
		storage_id: 'home::test',
		storage: 224,
		item_source: 531080,
		file_source: 531080,
		file_parent: 419336,
		file_target: '/Folder',
		item_size: 0,
		item_mtime: 1688721623,
		share_with: 'user00',
		share_with_displayname: 'User00',
		share_with_displayname_unique: 'user00@domain.com',
		status: {
			status: 'away',
			message: null,
			icon: null,
			clearAt: null,
		},
		mail_send: 0,
		hide_download: 0,
		attributes: null,
		tags: [window.OC.TAG_FAVORITE],
	}

	test('File', async () => {
		jest.spyOn(axios, 'get').mockReturnValueOnce(Promise.resolve({
			data: {
				ocs: {
					data: [shareFile],
				},
			},
		}))

		const shares = await getContents(false, true, false, false)

		expect(axios.get).toHaveBeenCalledTimes(1)
		expect(shares.contents).toHaveLength(1)

		const file = shares.contents[0] as File
		expect(file).toBeInstanceOf(File)
		expect(file.fileid).toBe(530936)
		expect(file.source).toBe('http://localhost/remote.php/dav/files/test/document.md')
		expect(file.owner).toBe('test')
		expect(file.mime).toBe('text/markdown')
		expect(file.mtime).toBeInstanceOf(Date)
		expect(file.size).toBe(123)
		expect(file.permissions).toBe(27)
		expect(file.root).toBe('/files/test')
		expect(file.attributes).toBeInstanceOf(Object)
		expect(file.attributes['has-preview']).toBe(true)
		expect(file.attributes.previewUrl).toBe('/index.php/core/preview?fileId=530936&x=32&y=32&forceIcon=0')
		expect(file.attributes.favorite).toBe(0)
	})

	test('Folder', async () => {
		jest.spyOn(axios, 'get').mockReturnValueOnce(Promise.resolve({
			data: {
				ocs: {
					data: [shareFolder],
				},
			},
		}))

		const shares = await getContents(false, true, false, false)

		expect(axios.get).toHaveBeenCalledTimes(1)
		expect(shares.contents).toHaveLength(1)

		const folder = shares.contents[0] as Folder
		expect(folder).toBeInstanceOf(Folder)
		expect(folder.fileid).toBe(531080)
		expect(folder.source).toBe('http://localhost/remote.php/dav/files/test/Folder')
		expect(folder.owner).toBe('test')
		expect(folder.mime).toBe('httpd/unix-directory')
		expect(folder.mtime).toBeInstanceOf(Date)
		expect(folder.size).toBe(0)
		expect(folder.permissions).toBe(31)
		expect(folder.root).toBe('/files/test')
		expect(folder.attributes).toBeInstanceOf(Object)
		expect(folder.attributes['has-preview']).toBe(false)
		expect(folder.attributes.previewUrl).toBeUndefined()
		expect(folder.attributes.favorite).toBe(1)
	})

	test('Error', async () => {
		jest.spyOn(logger, 'error').mockImplementationOnce(() => {})
		jest.spyOn(axios, 'get').mockReturnValueOnce(Promise.resolve({
			data: {
				ocs: {
					data: [{}],
				},
			},
		}))

		const shares = await getContents(false, true, false, false)
		expect(shares.contents).toHaveLength(0)
		expect(logger.error).toHaveBeenCalledTimes(1)
	})
})
