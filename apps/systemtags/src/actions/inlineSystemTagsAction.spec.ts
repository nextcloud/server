/**
 * @copyright Copyright (c) 2023 Lucas Azevedo <lhs_azevedo@hotmail.com>
 *
 * @author Lucas Azevedo <lhs_azevedo@hotmail.com>
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
import { action } from './inlineSystemTagsAction'
import { expect } from '@jest/globals'
import { File, Permission, View, FileAction } from '@nextcloud/files'

const view = {
	id: 'files',
	name: 'Files',
} as View

describe('Inline system tags action conditions tests', () => {
	test('Default values', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
		})

		expect(action).toBeInstanceOf(FileAction)
		expect(action.id).toBe('system-tags')
		expect(action.displayName([file], view)).toBe('')
		expect(action.iconSvgInline([], view)).toBe('')
		expect(action.default).toBeUndefined()
		expect(action.enabled).toBeUndefined()
		expect(action.order).toBe(0)
	})
})

describe('Inline system tags action render tests', () => {
	test('Render nothing when Node does not have system tags', async () => {
		const file = new File({
			id: 1,
			source: 'http://localhost/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
		})

		const result = await action.renderInline!(file, view)
		expect(result).toBeNull()
	})

	test('Render a single system tag', async () => {
		const file = new File({
			id: 1,
			source: 'http://localhost/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			attributes: {
				'system-tags': {
					'system-tag': 'Confidential',
				},
			},
		})

		const result = await action.renderInline!(file, view)
		expect(result).toBeInstanceOf(HTMLElement)
		expect(result!.outerHTML).toBe(
			'<ul class="files-list__system-tags" aria-label="This file has the tag Confidential">'
				+ '<li class="files-list__system-tag">Confidential</li>'
			+ '</ul>',
		)
	})

	test('Render two system tags', async () => {
		const file = new File({
			id: 1,
			source: 'http://localhost/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			attributes: {
				'system-tags': {
					'system-tag': [
						'Important',
						'Confidential',
					],
				},
			},
		})

		const result = await action.renderInline!(file, view)
		expect(result).toBeInstanceOf(HTMLElement)
		expect(result!.outerHTML).toBe(
			'<ul class="files-list__system-tags" aria-label="This file has the tags Important and Confidential">'
				+ '<li class="files-list__system-tag">Important</li>'
				+ '<li class="files-list__system-tag files-list__system-tag--more" title="Confidential">+1</li>'
			+ '</ul>',
		)
	})

	test('Render multiple system tags', async () => {
		const file = new File({
			id: 1,
			source: 'http://localhost/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			attributes: {
				'system-tags': {
					'system-tag': [
						'Important',
						'Confidential',
						'Secret',
						'Classified',
					],
				},
			},
		})

		const result = await action.renderInline!(file, view)
		expect(result).toBeInstanceOf(HTMLElement)
		expect(result!.outerHTML).toBe(
			'<ul class="files-list__system-tags" aria-label="This file has the tags Important, Confidential, Secret and Classified">'
				+ '<li class="files-list__system-tag">Important</li>'
				+ '<li class="files-list__system-tag files-list__system-tag--more" title="Confidential, Secret, Classified">+3</li>'
			+ '</ul>',
		)
	})
})
