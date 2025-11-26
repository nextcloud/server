/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Folder, Navigation } from '@nextcloud/files'

import FolderSvg from '@mdi/svg/svg/folder.svg?raw'
import { getNavigation, View } from '@nextcloud/files'
import { createTestingPinia } from '@pinia/testing'
import { cleanup, fireEvent, getAllByRole, render } from '@testing-library/vue'
import { afterEach, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest'
import NavigationView from './FilesNavigation.vue'
import router from '../router/router.ts'
import RouterService from '../services/RouterService.ts'
import { useViewConfigStore } from '../store/viewConfig.ts'

afterEach(() => removeInitialState())
beforeAll(async () => {
	Object.defineProperty(document.documentElement, 'clientWidth', { value: 1920 })
	await fireEvent.resize(window)
})

describe('Navigation', () => {
	beforeEach(cleanup)

	beforeEach(async () => {
		delete window._nc_navigation
		mockWindow()
		getNavigation().register(createView('files', 'Files'))
		await router.replace({ name: 'filelist', params: { view: 'files' } })
	})

	it('renders navigation with settings button and search', async () => {
		const component = render(NavigationView, {
			router,
			global: {
				plugins: [createTestingPinia({
					createSpy: vi.fn,
				})],
			},
		})

		// see the navigation
		await expect(component.findByRole('navigation', { name: 'Files' })).resolves.not.toThrow()
		// see the search box
		await expect(component.findByRole('searchbox', { name: /Search here/ })).resolves.not.toThrow()
		// see the settings entry
		await expect(component.findByRole('link', { name: /Files settings/ })).resolves.not.toThrow()
	})

	it('renders no quota without storage stats', () => {
		const component = render(NavigationView, {
			router,
			global: {
				plugins: [createTestingPinia({
					createSpy: vi.fn,
				})],
			},
		})

		expect(component.baseElement.querySelector('[data-cy-files-navigation-settings-quota]')).toBeNull()
	})

	it('Unlimited quota shows used storage but no progressbar', async () => {
		mockInitialState('files', 'storageStats', {
			used: 1024 * 1024 * 1024,
			quota: -1,
			total: 50 * 1024 * 1024 * 1024,
		})

		const component = render(NavigationView, {
			router,
			global: {
				plugins: [createTestingPinia({
					createSpy: vi.fn,
				})],
			},
		})

		expect(component.baseElement.querySelector('[data-cy-files-navigation-settings-quota]')).not.toBeNull()

		await expect(component.findByText('1 GB used')).resolves.not.toThrow()
		await expect(component.findByRole('progressbar')).rejects.toThrow()
	})

	it('Non-reached quota shows stats and progress', async () => {
		mockInitialState('files', 'storageStats', {
			used: 1024 * 1024 * 1024,
			quota: 5 * 1024 * 1024 * 1024,
			total: 5 * 1024 * 1024 * 1024,
			relative: 20, // percent
		})

		const component = render(NavigationView, {
			router,
			global: {
				plugins: [createTestingPinia({
					createSpy: vi.fn,
				})],
			},
		})

		await expect(component.findByText('1 GB of 5 GB used')).resolves.not.toThrow()
		await expect(component.findByRole('progressbar')).resolves.not.toThrow()
		expect((component.getByRole('progressbar') as HTMLProgressElement).value).toBe(20)
	})

	it('Reached quota', async () => {
		mockInitialState('files', 'storageStats', {
			used: 5 * 1024 * 1024 * 1024,
			quota: 1024 * 1024 * 1024,
			total: 1024 * 1024 * 1024,
			relative: 500, // percent
		})

		const component = render(NavigationView, {
			router,
			global: {
				plugins: [createTestingPinia({
					createSpy: vi.fn,
				})],
			},
		})

		await expect(component.findByText('5 GB of 1 GB used')).resolves.not.toThrow()
		await expect(component.findByRole('progressbar')).resolves.not.toThrow()
		expect((component.getByRole('progressbar') as HTMLProgressElement).value).toBe(100)
	})
})

describe('Navigation API', () => {
	let Navigation: Navigation

	beforeEach(async () => {
		delete window._nc_navigation
		Navigation = getNavigation()
		mockWindow()

		await router.replace({ name: 'filelist', params: { view: 'files' } })
	})

	beforeEach(resetNavigation)
	beforeEach(cleanup)

	it('Check API entries rendering', async () => {
		Navigation.register(createView('files', 'Files'))

		const component = render(NavigationView, {
			router,
			global: {
				plugins: [
					createTestingPinia({
						createSpy: vi.fn,
					}),
				],
			},
		})

		// see the navigation
		await expect(component.findByRole('navigation', { name: 'Files' })).resolves.not.toThrow()
		// see the views
		await expect(component.findByRole('list', { name: 'Views' })).resolves.not.toThrow()
		// see the entry
		await expect(component.findByRole('link', { name: 'Files' })).resolves.not.toThrow()
		// see that the entry has all props
		const entry = component.getByRole('link', { name: 'Files' })
		expect(entry.getAttribute('href')).toMatch(/\/apps\/files\/files$/)
		expect(entry.getAttribute('aria-current')).toBe('page')
		expect(entry.getAttribute('title')).toBe('Files')
	})

	it('Adds a new entry and render', async () => {
		Navigation.register(createView('files', 'Files'))
		Navigation.register(createView('sharing', 'Sharing'))

		const component = render(NavigationView, {
			router,
			global: {
				plugins: [
					createTestingPinia({
						createSpy: vi.fn,
					}),
				],
			},
		})

		const list = component.getByRole('list', { name: 'Views' })
		expect(getAllByRole(list, 'listitem')).toHaveLength(2)

		await expect(component.findByRole('link', { name: 'Files' })).resolves.not.toThrow()
		await expect(component.findByRole('link', { name: 'Sharing' })).resolves.not.toThrow()
		// see that the entry has all props
		const entry = component.getByRole('link', { name: 'Sharing' })
		expect(entry.getAttribute('href')).toMatch(/\/apps\/files\/sharing$/)
		expect(entry.getAttribute('aria-current')).toBeNull()
		expect(entry.getAttribute('title')).toBe('Sharing')
	})

	it('Adds a new children, render and open menu', async () => {
		Navigation.register(createView('files', 'Files'))
		Navigation.register(createView('sharing', 'Sharing'))
		Navigation.register(createView('sharingin', 'Shared with me', 'sharing'))

		const component = render(NavigationView, {
			router,
			global: {
				plugins: [
					createTestingPinia({
						createSpy: vi.fn,
					}),
				],
			},
		})
		const viewConfigStore = useViewConfigStore()

		const list = component.getByRole('list', { name: 'Views' })
		expect(getAllByRole(list, 'listitem')).toHaveLength(3)

		// Toggle the sharing entry children
		const entry = component.getByRole('link', { name: 'Sharing' })
		expect(entry.getAttribute('aria-expanded')).toBe('false')
		await fireEvent.click(component.getByRole('button', { name: 'Open menu' }))
		expect(entry.getAttribute('aria-expanded')).toBe('true')

		// Expect store update to be called
		expect(viewConfigStore.update).toHaveBeenCalled()
		expect(viewConfigStore.update).toHaveBeenCalledWith('sharing', 'expanded', true)

		// Validate children
		await expect(component.findByRole('link', { name: 'Shared with me' })).resolves.not.toThrow()

		await fireEvent.click(component.getByRole('button', { name: 'Collapse menu' }))
		// Expect store update to be called
		expect(viewConfigStore.update).toHaveBeenCalledWith('sharing', 'expanded', false)
	})
})

/**
 * Remove the mocked initial state
 */
function removeInitialState(): void {
	document.querySelectorAll('input[type="hidden"]').forEach((el) => {
		el.remove()
	})
	// clear the cache
	delete globalThis._nc_initial_state
}

/**
 * Helper to mock an initial state value
 * @param app - The app
 * @param key - The key
 * @param value - The value
 */
function mockInitialState(app: string, key: string, value: unknown): void {
	const el = document.createElement('input')
	el.value = btoa(JSON.stringify(value))
	el.id = `initial-state-${app}-${key}`
	el.type = 'hidden'

	document.head.appendChild(el)
}

function resetNavigation() {
	const nav = getNavigation()
	;[...nav.views].forEach(({ id }) => nav.remove(id))
	nav.setActive(null)
}

function createView(id: string, name: string, parent?: string) {
	return new View({
		id,
		name,
		getContents: async () => ({ folder: {} as Folder, contents: [] }),
		icon: FolderSvg,
		order: 1,
		parent,
	})
}

function mockWindow() {
	window.OCP ??= {}
	window.OCP.Files ??= {}
	window.OCP.Files.Router = new RouterService(router)
}
