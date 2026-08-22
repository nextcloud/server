/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import { renderFilesApp } from './renderFilesApp.ts'

vi.mock('../FilesApp.vue', () => ({
	default: {
		name: 'FilesApp',
		render: (h) => h('div', { attrs: { id: 'files-app-stub' } }),
	},
}))

describe('renderFilesApp', () => {
	beforeEach(() => {
		document.body.innerHTML = ''
	})

	it('mounts the Files app into the given element', () => {
		const el = document.createElement('div')
		document.body.appendChild(el)

		renderFilesApp(el, 'files')

		expect(document.body.querySelector('#files-app-stub')).not.toBeNull()
	})

	it('activates the requested view without touching the browser URL', () => {
		const el = document.createElement('div')
		document.body.appendChild(el)
		const before = window.location.href

		renderFilesApp(el, 'some-view')

		expect(window.location.href).toBe(before)
	})
})
