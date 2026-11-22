/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { TypeFilter } from './TypeFilter.ts'

import { getFileListFilters } from '@nextcloud/files'
import { beforeAll, describe, expect, it } from 'vitest'
import { nextTick } from 'vue'
import { registerTypeFilter } from './TypeFilter.ts'

const TAG_NAME = 'files-file-list-filter-type'

/** Mount the filter's custom element the way `FileListFilters.vue` does. */
async function mountFilterElement(filter: TypeFilter) {
	const element = document.createElement(TAG_NAME) as HTMLElement & { filter?: TypeFilter }
	// The Vue renderer assigns `.prop` bindings before inserting the element
	element.filter = filter
	document.body.appendChild(element)
	await nextTick()
	await nextTick()
	return element
}

describe('TypeFilter', () => {
	let filter: TypeFilter

	beforeAll(() => {
		window.OC = { ...window.OC, MimeTypeList: { aliases: {} } }
		registerTypeFilter()
		filter = getFileListFilters().find(({ id }) => id === 'files:type') as TypeFilter
	})

	it('registers the filter and its custom element', () => {
		expect(filter).toBeTruthy()
		expect(filter.tagName).toBe(TAG_NAME)
		expect(customElements.get(TAG_NAME)).toBeTruthy()
	})

	it('renders the type presets', async () => {
		const element = await mountFilterElement(filter)

		expect(element.textContent).toContain('Folders')
		expect(element.textContent).toContain('Spreadsheets')
	})

	// Resetting an empty selection used to re-enter `setPresets()`, which resets
	// the filter again — an endless update loop that left the filter unusable.
	it('does not loop when resetting an empty selection', async () => {
		const element = await mountFilterElement(filter)

		filter.reset()
		await nextTick()

		expect(filter.presets).toEqual([])
		expect(element.textContent).toContain('Folders')
	})

	it('filters nodes by the selected presets', async () => {
		await mountFilterElement(filter)

		filter.setPresets([{ id: 'folder', label: 'Folders', icon: '', mime: ['httpd/unix-directory'] }])

		const nodes = [
			{ mime: 'httpd/unix-directory' },
			{ mime: 'text/plain' },
		] as Parameters<TypeFilter['filter']>[0]
		expect(filter.filter(nodes)).toEqual([{ mime: 'httpd/unix-directory' }])

		filter.setPresets([])
		expect(filter.filter(nodes)).toEqual(nodes)
	})
})
