/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { File } from '@nextcloud/files'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest'
import { getPinia } from '../store/index.ts'
import { useSidebarStore } from '../store/sidebar.ts'
import { logger } from '../utils/logger.ts'
import { isSidebarMounted, mountSidebar } from './mount.ts'
import { getSidebarDataProvider, resetSidebarDataProvider, setSidebarDataProvider } from './provider.ts'
import { createFilesStoreDataProvider } from './providers/filesStore.ts'
import { createStandaloneDataProvider } from './providers/standalone.ts'
import { exposeSidebarApi, exposeSidebarMount, initializeSidebar, renderSidebar } from './setup.ts'

vi.mock('@nextcloud/auth')
vi.mock('./mount.ts', () => ({
	mountSidebar: vi.fn(() => true),
	isSidebarMounted: vi.fn(() => false),
}))

/**
 * Create the content element of the page.
 */
function buildPageContent(): HTMLElement {
	const content = document.createElement('div')
	content.id = 'content'
	document.body.appendChild(content)
	return content
}

describe('Sidebar setup', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		setActivePinia(createPinia())
		resetSidebarDataProvider()
		delete window.OCA.Files
		document.body.innerHTML = ''
	})

	afterEach(() => {
		resetSidebarDataProvider()
	})

	test('renders the sidebar into the page content if no app provides the data', () => {
		const content = buildPageContent()

		initializeSidebar()

		expect(mountSidebar).toHaveBeenCalledWith(content)
		expect(getSidebarDataProvider()).toBeDefined()
	})

	test('does not render the sidebar if the page has no content element', () => {
		vi.spyOn(logger, 'error').mockImplementation(() => {})

		initializeSidebar()

		expect(mountSidebar).not.toHaveBeenCalled()
		expect(logger.error).toHaveBeenCalledOnce()
	})

	test('keeps the sidebar of the app rendering it', () => {
		buildPageContent()
		const provider = createFilesStoreDataProvider()
		setSidebarDataProvider(provider)

		initializeSidebar()

		expect(mountSidebar).not.toHaveBeenCalled()
		expect(getSidebarDataProvider()).toBe(provider)
	})

	test('exposes the sidebar implementation for the library proxy', () => {
		buildPageContent()

		initializeSidebar()

		expect(window.OCA.Files!._sidebar).toBeTypeOf('function')

		const sidebar = window.OCA.Files!._sidebar!()
		expect(sidebar.isOpen).toBe(false)
		expect(sidebar.node).toBeUndefined()
		expect(sidebar.activeTab).toBeUndefined()
	})

	test('does not expose the implementation if the sidebar cannot be rendered', () => {
		buildPageContent()
		vi.mocked(mountSidebar).mockReturnValueOnce(false)

		initializeSidebar()

		expect(window.OCA.Files?._sidebar).toBeUndefined()
	})
})

describe('Rendering the sidebar within an app', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		setActivePinia(createPinia())
		resetSidebarDataProvider()
		delete window.OCA.Files
		document.body.innerHTML = ''
	})

	afterEach(() => {
		resetSidebarDataProvider()
	})

	test('renders the sidebar into the requested element', () => {
		const target = document.createElement('div')

		renderSidebar(target)

		expect(mountSidebar).toHaveBeenCalledWith(target)
		expect(getSidebarDataProvider()).toBeDefined()
		expect(window.OCA.Files!._sidebar).toBeTypeOf('function')
	})

	test('moves an already rendered sidebar into another element', () => {
		const content = buildPageContent()
		const target = document.createElement('div')

		renderSidebar(content)
		renderSidebar(target)

		// the data provider is kept, so the sidebar keeps its state when it is moved
		expect(mountSidebar).toHaveBeenCalledTimes(2)
		expect(mountSidebar).toHaveBeenLastCalledWith(target)
	})

	test('is not rendered again automatically if the app already rendered it', () => {
		renderSidebar(document.createElement('div'))
		vi.mocked(mountSidebar).mockClear()
		vi.mocked(isSidebarMounted).mockReturnValue(true)

		initializeSidebar()

		expect(mountSidebar).not.toHaveBeenCalled()
	})

	test('does nothing if the current app renders the sidebar itself', () => {
		setSidebarDataProvider(createFilesStoreDataProvider())

		renderSidebar(document.createElement('div'))

		expect(mountSidebar).not.toHaveBeenCalled()
	})

	test('is exposed for the library proxy before a sidebar is rendered', () => {
		exposeSidebarMount()

		expect(window.OCA.Files!._mountSidebar).toBeTypeOf('function')
		// the sidebar is only available once it is rendered
		expect(window.OCA.Files!._sidebar).toBeUndefined()

		const target = document.createElement('div')
		window.OCA.Files!._mountSidebar!(target)

		expect(mountSidebar).toHaveBeenCalledWith(target)
		expect(window.OCA.Files!._sidebar).toBeTypeOf('function')
	})
})

const node = new File({
	id: 1,
	source: 'https://cloud.example.com/remote.php/dav/files/test/file.txt',
	owner: 'test',
	mime: 'text/plain',
	root: '/files/test',
})

describe('Sidebar API', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		resetSidebarDataProvider()
		delete window.OCA.Files
		setSidebarDataProvider(createStandaloneDataProvider())
	})

	afterEach(() => {
		resetSidebarDataProvider()
	})

	test('exposes the implementation of the `ISidebar` interface', () => {
		exposeSidebarApi()

		const sidebar = window.OCA.Files!._sidebar!()
		expect(sidebar.isOpen).toBe(false)
		expect(sidebar.activeTab).toBeUndefined()
		expect(sidebar.node).toBeUndefined()
		expect(sidebar.getTabs()).toEqual([])
		expect(sidebar.getActions()).toEqual([])
		expect(sidebar.open).toBeTypeOf('function')
		expect(sidebar.close).toBeTypeOf('function')
		expect(sidebar.setActiveTab).toBeTypeOf('function')
		expect(sidebar.setFullScreenMode).toBeTypeOf('function')
	})

	test('exposes the state of the sidebar store', () => {
		exposeSidebarApi()
		const sidebar = window.OCA.Files!._sidebar!()

		sidebar.open(node)

		expect(sidebar.isOpen).toBe(true)
		expect(sidebar.node).toBe(node)
		expect(useSidebarStore(getPinia()).currentNode).toBe(node)

		sidebar.close()
		expect(sidebar.isOpen).toBe(false)
	})

	test('keeps the existing files namespace', () => {
		window.OCA.Files = { Settings: 'untouched' } as unknown as typeof window.OCA.Files

		exposeSidebarApi()

		expect(window.OCA.Files).toMatchObject({ Settings: 'untouched' })
		expect(window.OCA.Files!._sidebar).toBeTypeOf('function')
	})
})
