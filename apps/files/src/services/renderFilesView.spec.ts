/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type RouterService from './RouterService.ts'

import { getNavigation, View } from '@nextcloud/files'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { h } from 'vue'
import { mountSidebar, unmountSidebar } from '../sidebar/mount.ts'
import { renderFilesView } from './renderFilesView.ts'

const VIEW_ID = 'test-view'
const renderedViews: Array<ReturnType<typeof renderFilesView>> = []

function mountFilesView(el: HTMLElement, options?: Parameters<typeof renderFilesView>[2]) {
	const rendered = renderFilesView(el, VIEW_ID, options)
	renderedViews.push(rendered)
	return rendered
}

vi.mock('../sidebar/mount.ts', () => ({
	mountSidebar: vi.fn(() => true),
	unmountSidebar: vi.fn(),
}))

vi.mock('../views/FilesList.vue', () => ({
	default: {
		name: 'FilesList',
		props: { embedded: { type: Boolean, default: false }, rootDir: { type: String, default: '/' } },
		render() {
			return h('div', { id: 'files-list-stub', 'data-embedded': String(this.embedded), 'data-root-dir': this.rootDir })
		},
	},
}))

describe('renderFilesView', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		document.body.innerHTML = ''
		window.OCP = { Files: { Router: {} as RouterService } } as unknown as typeof window.OCP
		renderedViews.length = 0
		getNavigation().register(new View({
			id: VIEW_ID,
			name: 'Test view',
			icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" /></svg>',
			getContents: async () => ({ folder: {} as never, contents: [] }),
		}))
	})

	afterEach(() => {
		for (const rendered of renderedViews) {
			rendered.destroy()
		}
		getNavigation().remove(VIEW_ID)
	})

	it('mounts only the file list, embedded, into the given element', async () => {
		const el = document.createElement('div')
		document.body.appendChild(el)

		mountFilesView(el)

		const stub = await vi.waitFor(() => {
			const stub = document.body.querySelector('#files-list-stub')
			expect(stub).not.toBeNull()
			return stub
		})
		expect(stub?.getAttribute('data-embedded')).toBe('true')
	})

	it('activates the requested view without touching the browser URL', () => {
		const el = document.createElement('div')
		document.body.appendChild(el)
		const before = window.location.href

		mountFilesView(el)

		expect(window.location.href).toBe(before)
		expect(getNavigation().active?.id).toBe(VIEW_ID)
	})

	it('routes embedded navigation in memory and restores the previous router on destroy', async () => {
		const el = document.createElement('div')
		document.body.appendChild(el)
		const previousRouter = window.OCP.Files.Router
		const before = window.location.href

		const rendered = mountFilesView(el)
		await window.OCP.Files.Router.goToRoute('filelist', { view: VIEW_ID })

		expect(window.location.href).toBe(before)
		expect(window.OCP.Files.Router).not.toBe(previousRouter)

		rendered.destroy()
		expect(window.OCP.Files.Router).toBe(previousRouter)
	})

	it('returns a handle that unmounts the file list', async () => {
		const el = document.createElement('div')
		document.body.appendChild(el)

		const rendered = mountFilesView(el)
		await vi.waitFor(() => expect(document.body.querySelector('#files-list-stub')).not.toBeNull())
		rendered.destroy()

		expect(document.body.querySelector('#files-list-stub')).toBeNull()
	})

	it('rejects another embed while one is active and allows one after destroy', () => {
		const firstEl = document.createElement('div')
		const secondEl = document.createElement('div')
		document.body.append(firstEl, secondEl)
		const previousRouter = window.OCP.Files.Router
		const rendered = mountFilesView(firstEl)
		const embeddedRouter = window.OCP.Files.Router

		expect(() => renderFilesView(secondEl, VIEW_ID)).toThrow('Only one embedded Files view can be active at a time')
		expect(window.OCP.Files.Router).toBe(embeddedRouter)

		rendered.destroy()
		expect(window.OCP.Files.Router).toBe(previousRouter)
		const secondRendered = mountFilesView(secondEl)
		secondRendered.destroy()
	})

	it('opens the requested directory', async () => {
		const el = document.createElement('div')
		document.body.appendChild(el)

		mountFilesView(el, { dir: '/Team folder' })

		await vi.waitFor(() => expect(window.OCP.Files.Router.query).toEqual({ dir: '/Team folder' }))
		expect(window.OCP.Files.Router.params).toMatchObject({ view: VIEW_ID })
	})

	it('starts the breadcrumbs with the root directory and opens it', async () => {
		const el = document.createElement('div')
		document.body.appendChild(el)

		mountFilesView(el, { rootDir: '/Team folder' })

		const stub = await vi.waitFor(() => {
			const stub = document.body.querySelector('#files-list-stub')
			expect(stub).not.toBeNull()
			return stub
		})
		expect(stub?.getAttribute('data-root-dir')).toBe('/Team folder')
		await vi.waitFor(() => expect(window.OCP.Files.Router.query).toEqual({ dir: '/Team folder' }))
	})

	it('does not render the sidebar unless an element is given', () => {
		const el = document.createElement('div')
		document.body.appendChild(el)

		mountFilesView(el).destroy()

		expect(mountSidebar).not.toHaveBeenCalled()
		expect(unmountSidebar).not.toHaveBeenCalled()
	})

	it('renders the sidebar into the given element and removes it on destroy', () => {
		const el = document.createElement('div')
		const sidebarEl = document.createElement('div')
		document.body.append(el, sidebarEl)

		const rendered = mountFilesView(el, { sidebarEl })
		expect(mountSidebar).toHaveBeenCalledWith(sidebarEl)
		expect(unmountSidebar).not.toHaveBeenCalled()

		rendered.destroy()
		expect(unmountSidebar).toHaveBeenCalledOnce()
	})
})
