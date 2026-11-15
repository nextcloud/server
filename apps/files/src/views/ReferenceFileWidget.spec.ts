/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFile } from '@nextcloud/files'

import { mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

const viewer = vi.hoisted(() => ({ canView: vi.fn(), open: vi.fn() }))
vi.mock('@nextcloud/viewer', () => ({
	canView: viewer.canView,
	getViewer: () => ({ open: viewer.open }),
}))
const user = vi.hoisted(() => ({ current: { uid: 'alice' } as { uid: string } | null }))
vi.mock('@nextcloud/auth', () => ({ getCurrentUser: () => user.current }))

const { default: ReferenceFileWidget } = await import('./ReferenceFileWidget.vue')

// What the viewer's element keeps of what it is given
class ViewerFileStub extends HTMLElement {
	file?: IFile
}
if (!customElements.get('nc-viewer-file')) {
	customElements.define('nc-viewer-file', ViewerFileStub)
}

const richObject = {
	id: 42,
	name: 'beach.jpg',
	size: 2048,
	path: 'Photos/beach.jpg',
	link: 'https://cloud.example.com/f/42',
	mimetype: 'image/jpeg',
	mtime: 1760000000,
	'preview-available': true,
}

/**
 * Show the widget for a file.
 *
 * @param overrides - What differs from the picture above
 */
function mountWidget(overrides: Record<string, unknown> = {}) {
	return mount(ReferenceFileWidget, {
		props: { richObject: { ...richObject, ...overrides } },
		global: { mocks: { t: (_app: string, text: string) => text } },
		attachTo: document.body,
	})
}

beforeEach(() => {
	user.current = { uid: 'alice' }
	viewer.canView.mockReturnValue(true)
	window.OC = { ...window.OC, Util: { humanFileSize: () => '2 KB', relativeModifiedDate: () => 'a week ago' } } as typeof window.OC
})

afterEach(() => {
	vi.clearAllMocks()
	document.body.innerHTML = ''
})

describe('ReferenceFileWidget', () => {
	it('shows the file with the viewer\'s own element', () => {
		const wrapper = mountWidget()

		const element = wrapper.find('nc-viewer-file').element as ViewerFileStub
		expect(element.file?.fileid).toBe(42)
		expect(element.file?.displayname).toBe('beach.jpg')
		expect(element.file?.source).toContain('/remote.php/dav/files/alice/Photos/beach.jpg')
	})

	it('hands the viewer the permissions the reference carries', () => {
		const wrapper = mountWidget({ permissions: 27 })

		expect((wrapper.find('nc-viewer-file').element as ViewerFileStub).file?.permissions).toBe(27)
	})

	// The viewer then offers no download, and asks for the preview by hand
	it('hands the viewer what a share forbids', () => {
		const wrapper = mountWidget({ 'hide-download': 'yes', 'can-download': 'no' })

		const { attributes } = (wrapper.find('nc-viewer-file').element as ViewerFileStub).file!
		expect(attributes['hide-download']).toBe(true)
		expect(JSON.parse(attributes['share-attributes'])).toEqual([{ scope: 'permissions', key: 'download', value: false }])
	})

	it('lets the viewer download a file of a share that allows it', () => {
		const wrapper = mountWidget({ 'hide-download': 'no', 'can-download': 'yes' })

		const { attributes } = (wrapper.find('nc-viewer-file').element as ViewerFileStub).file!
		expect(attributes['hide-download']).toBe(false)
		expect(attributes['share-attributes']).toBeUndefined()
	})

	it('falls back to its link when the viewer cannot show the file', async () => {
		const wrapper = mountWidget()

		wrapper.find('nc-viewer-file').element.dispatchEvent(new CustomEvent('errored'))
		await wrapper.vm.$nextTick()

		expect(wrapper.find('nc-viewer-file').exists()).toBe(false)
		expect(wrapper.find('a.widget-file--link').exists()).toBe(true)
	})

	it('is a link to a file the viewer has nothing for', () => {
		viewer.canView.mockReturnValue(false)
		const wrapper = mountWidget({ name: 'report.zip', mimetype: 'application/zip' })

		expect(wrapper.find('nc-viewer-file').exists()).toBe(false)
		expect(wrapper.find('a.widget-file--link').exists()).toBe(true)
	})

	// A guest has no files of their own the path would be in
	it('is a link for a guest', () => {
		user.current = null
		const wrapper = mountWidget()

		expect(wrapper.find('nc-viewer-file').exists()).toBe(false)
		expect(viewer.canView).not.toHaveBeenCalled()
	})

	it('opens the file in the viewer from its link when it is not shown inline', async () => {
		const wrapper = mount(ReferenceFileWidget, {
			props: { richObject, interactive: false },
			global: { mocks: { t: (_app: string, text: string) => text } },
		})

		await wrapper.find('a.widget-file--link').trigger('click')

		expect(viewer.open).toHaveBeenCalledOnce()
		const [files, file] = viewer.open.mock.calls[0]!
		expect(files).toHaveLength(1)
		expect(file.fileid).toBe(42)
	})
})
