/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { View, getNavigation } from '@nextcloud/files'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { renderFilesView } from './renderFilesView.ts'

const VIEW_ID = 'test-view'

vi.mock('../views/FilesList.vue', () => ({
	default: {
		name: 'FilesList',
		props: { embedded: { type: Boolean, default: false } },
		render(h) {
			return h('div', { attrs: { id: 'files-list-stub', 'data-embedded': String(this.embedded) } })
		},
	},
}))

describe('renderFilesView', () => {
	beforeEach(() => {
		document.body.innerHTML = ''
		getNavigation().register(new View({
			id: VIEW_ID,
			name: 'Test view',
			icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" /></svg>',
			getContents: async () => ({ folder: {} as never, contents: [] }),
		}))
	})

	afterEach(() => {
		getNavigation().remove(VIEW_ID)
	})

	it('mounts only the file list, embedded, into the given element', () => {
		const el = document.createElement('div')
		document.body.appendChild(el)

		renderFilesView(el, VIEW_ID)

		const stub = document.body.querySelector('#files-list-stub')
		expect(stub).not.toBeNull()
		expect(stub?.getAttribute('data-embedded')).toBe('true')
	})

	it('activates the requested view without touching the browser URL', () => {
		const el = document.createElement('div')
		document.body.appendChild(el)
		const before = window.location.href

		renderFilesView(el, VIEW_ID)

		expect(window.location.href).toBe(before)
		expect(getNavigation().active?.id).toBe(VIEW_ID)
	})

	it('returns a handle that unmounts the file list', () => {
		const el = document.createElement('div')
		document.body.appendChild(el)

		const rendered = renderFilesView(el, VIEW_ID)
		rendered.destroy()

		expect(document.body.querySelector('#files-list-stub')).toBeNull()
	})
})
