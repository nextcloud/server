/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
		expect(action.enabled).toBeDefined()
		expect(action.order).toBe(0)
		expect(action.enabled!([file], view)).toBe(false)
	})

	test('Enabled with valid system tags', () => {
		const file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
			attributes: {
				'system-tags': {
					'system-tag': 'Confidential',
				},
			},
		})

		expect(action.enabled!([file], view)).toBe(true)
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
