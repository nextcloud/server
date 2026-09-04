/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { logger as Logger } from '../utils/logger.ts'
import type { isSidebarMounted as IsSidebarMounted, mountSidebar as MountSidebar } from './mount.ts'

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, test, vi } from 'vitest'

vi.mock('@nextcloud/auth')

// the sidebar is only rendered once per page, so every test needs a fresh module state
let mountSidebar: typeof MountSidebar
let isSidebarMounted: typeof IsSidebarMounted
let logger: typeof Logger

/**
 * Create the content element of the page.
 *
 * @param id - The id of the content element
 */
function buildPageContent(id = 'content'): HTMLElement {
	const content = document.createElement('div')
	content.id = id
	content.className = 'content'
	document.body.appendChild(content)
	return content
}

describe('Sidebar rendering', () => {
	beforeEach(async () => {
		vi.restoreAllMocks()
		vi.resetModules()
		setActivePinia(createPinia())
		document.body.innerHTML = '';

		({ logger } = await import('../utils/logger.ts'));
		({ isSidebarMounted, mountSidebar } = await import('./mount.ts'))
	})

	test('is not rendered by default', () => {
		expect(isSidebarMounted()).toBe(false)
	})

	test('does not render without an element to render into', () => {
		vi.spyOn(logger, 'error').mockImplementation(() => {})

		// apps are not typed, so this can happen with an element that was not rendered yet
		expect(mountSidebar(undefined as unknown as HTMLElement)).toBe(false)
		expect(isSidebarMounted()).toBe(false)
		expect(logger.error).toHaveBeenCalledOnce()
	})

	test('renders into the requested element', () => {
		const content = buildPageContent()

		expect(mountSidebar(content)).toBe(true)
		expect(isSidebarMounted()).toBe(true)
		// the mountpoint is replaced by the sidebar itself
		expect(content.children).toHaveLength(1)
		expect(content.querySelector('aside')).not.toBeNull()
	})

	test('is not rendered again for the same element', () => {
		const content = buildPageContent()

		mountSidebar(content)
		const sidebar = content.querySelector('aside')

		expect(mountSidebar(content)).toBe(true)
		expect(content.querySelectorAll('aside')).toHaveLength(1)
		expect(content.querySelector('aside')).toBe(sidebar)
	})

	test('moves into another element', () => {
		const content = buildPageContent()
		const target = document.createElement('div')
		document.body.appendChild(target)

		mountSidebar(content)
		expect(content.querySelector('aside')).not.toBeNull()

		expect(mountSidebar(target)).toBe(true)
		expect(content.querySelector('aside')).toBeNull()
		expect(target.querySelector('aside')).not.toBeNull()
	})
})
