/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { action } from './inlineSystemTagsAction'
import { beforeEach, describe, expect, test, vi } from 'vitest'
import { emit, subscribe } from '@nextcloud/event-bus'
import { File, Permission, View, FileAction } from '@nextcloud/files'
import { setNodeSystemTags } from '../utils'
import * as serviceTagApi from '../services/api'

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
		// Always enabled
		expect(action.enabled!([file], view)).toBe(true)
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

	beforeEach(() => {
		vi.spyOn(serviceTagApi, 'fetchTags').mockImplementation(async () => {
			return []
		})
	})

	test('Render something even when Node does not have system tags', async () => {
		const file = new File({
			id: 1,
			source: 'http://localhost/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
		})

		const result = await action.renderInline!(file, view)
		expect(result).toBeInstanceOf(HTMLElement)
		expect(result!.outerHTML).toMatchInlineSnapshot(
			'"<ul class="files-list__system-tags" aria-label="Assigned collaborative tags" data-systemtags-fileid="1"></ul>"',
		)
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
		expect(result!.outerHTML).toMatchInlineSnapshot(
			'"<ul class="files-list__system-tags" aria-label="Assigned collaborative tags" data-systemtags-fileid="1"><li class="files-list__system-tag" data-systemtag-name="Confidential">Confidential</li></ul>"',
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
					'system-tag': ['Important', 'Confidential'],
				},
			},
		})

		const result = await action.renderInline!(file, view)
		expect(result).toBeInstanceOf(HTMLElement)
		expect(result!.outerHTML).toMatchInlineSnapshot(
			'"<ul class="files-list__system-tags" aria-label="Assigned collaborative tags" data-systemtags-fileid="1"><li class="files-list__system-tag" data-systemtag-name="Important">Important</li><li class="files-list__system-tag" data-systemtag-name="Confidential">Confidential</li></ul>"',
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
		expect(result!.outerHTML).toMatchInlineSnapshot(
			'"<ul class="files-list__system-tags" aria-label="Assigned collaborative tags" data-systemtags-fileid="1"><li class="files-list__system-tag" data-systemtag-name="Important">Important</li><li class="files-list__system-tag files-list__system-tag--more" data-systemtag-name="+3" title="Confidential, Secret, Classified" aria-hidden="true" role="presentation">+3</li><li class="files-list__system-tag hidden-visually" data-systemtag-name="Confidential">Confidential</li><li class="files-list__system-tag hidden-visually" data-systemtag-name="Secret">Secret</li><li class="files-list__system-tag hidden-visually" data-systemtag-name="Classified">Classified</li></ul>"',
		)
	})

	test('Render gets updated on system tag change', async () => {
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

		const result = await action.renderInline!(file, view) as HTMLElement
		document.body.appendChild(result)
		expect(result).toBeInstanceOf(HTMLElement)
		expect(document.body.innerHTML).toMatchInlineSnapshot(
			'"<ul class="files-list__system-tags" aria-label="Assigned collaborative tags" data-systemtags-fileid="1"><li class="files-list__system-tag" data-systemtag-name="Important">Important</li><li class="files-list__system-tag files-list__system-tag--more" data-systemtag-name="+3" title="Confidential, Secret, Classified" aria-hidden="true" role="presentation">+3</li><li class="files-list__system-tag hidden-visually" data-systemtag-name="Confidential">Confidential</li><li class="files-list__system-tag hidden-visually" data-systemtag-name="Secret">Secret</li><li class="files-list__system-tag hidden-visually" data-systemtag-name="Classified">Classified</li></ul>"',
		)

		// Subscribe to the event
		const eventPromise = new Promise((resolve) => {
			subscribe('systemtags:node:updated', () => {
				setTimeout(resolve, 100)
			})
		})

		// Change tags
		setNodeSystemTags(file, ['Public'])
		emit('systemtags:node:updated', file)
		expect(file.attributes!['system-tags']!['system-tag']).toEqual(['Public'])

		// Wait for the event to be processed
		await eventPromise

		expect(document.body.innerHTML).toMatchInlineSnapshot(
			'"<ul class="files-list__system-tags" aria-label="Assigned collaborative tags" data-systemtags-fileid="1"><li class="files-list__system-tag" data-systemtag-name="Public">Public</li></ul>"',
		)
	})
})

describe('Inline system tags action colors', () => {

	const tag = {
		id: 1,
		displayName: 'Confidential',
		color: '000000',
		etag: '123',
		userVisible: true,
		userAssignable: true,
		canAssign: true,
	}

	beforeEach(() => {
		document.body.innerHTML = ''
		vi.spyOn(serviceTagApi, 'fetchTags').mockImplementation(async () => {
			return [tag]
		})
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
		expect(result!.outerHTML).toMatchInlineSnapshot(
			'"<ul class="files-list__system-tags" aria-label="Assigned collaborative tags" data-systemtags-fileid="1"><li class="files-list__system-tag" data-systemtag-name="Confidential" style="--systemtag-color: #000000;" data-systemtag-color="true">Confidential</li></ul>"',
		)
	})

	test('Render a single system tag with invalid WCAG color', async () => {
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

		document.body.setAttribute('data-themes', 'theme-dark')

		const result = await action.renderInline!(file, view)
		expect(result).toBeInstanceOf(HTMLElement)
		expect(result!.outerHTML).toMatchInlineSnapshot(
			'"<ul class="files-list__system-tags" aria-label="Assigned collaborative tags" data-systemtags-fileid="1"><li class="files-list__system-tag" data-systemtag-name="Confidential" style="--systemtag-color: #646464;" data-systemtag-color="true">Confidential</li></ul>"',
		)

		document.body.removeAttribute('data-themes')
	})

	test('Rendered color gets updated on system tag change', async () => {
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

		const result = await action.renderInline!(file, view) as HTMLElement
		document.body.appendChild(result)
		expect(result).toBeInstanceOf(HTMLElement)
		expect(document.body.innerHTML).toMatchInlineSnapshot(
			'"<ul class="files-list__system-tags" aria-label="Assigned collaborative tags" data-systemtags-fileid="1"><li class="files-list__system-tag" data-systemtag-name="Confidential" style="--systemtag-color: #000000;" data-systemtag-color="true">Confidential</li></ul>"',
		)

		// Subscribe to the event
		const eventPromise = new Promise((resolve) => {
			subscribe('systemtags:tag:updated', () => {
				setTimeout(resolve, 100)
			})
		})

		// Change tag color
		tag.color = '456789'
		emit('systemtags:tag:updated', tag)

		// Wait for the event to be processed
		await eventPromise

		expect(document.body.innerHTML).toMatchInlineSnapshot(
			'"<ul class="files-list__system-tags" aria-label="Assigned collaborative tags" data-systemtags-fileid="1"><li class="files-list__system-tag" data-systemtag-name="Confidential" style="--systemtag-color: #456789;" data-systemtag-color="true">Confidential</li></ul>"',
		)
	})
})
