/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { File, Permission, View } from '@nextcloud/files'
import { describe, it, vi, expect, beforeEach, beforeAll, afterEach } from 'vitest'
import { nextTick } from 'vue'
import axios from '@nextcloud/axios'

import { getPinia } from '../store/index.ts'
import { useActiveStore } from '../store/active.ts'

import { action as deleteAction } from '../actions/deleteAction.ts'
import { action as favoriteAction } from '../actions/favoriteAction.ts'
import { action as renameAction } from '../actions/renameAction.ts'
import { action as sidebarAction } from '../actions/sidebarAction.ts'
import { registerHotkeys } from './HotKeysService.ts'
import { useUserConfigStore } from '../store/userconfig.ts'

let file: File
const view = {
	id: 'files',
	name: 'Files',
} as View

vi.mock('../actions/sidebarAction.ts', { spy: true })
vi.mock('../actions/deleteAction.ts', { spy: true })
vi.mock('../actions/favoriteAction.ts', { spy: true })
vi.mock('../actions/renameAction.ts', { spy: true })

describe('HotKeysService testing', () => {
	const activeStore = useActiveStore(getPinia())

	const goToRouteMock = vi.fn()

	let initialState: HTMLInputElement

	afterEach(() => {
		document.body.removeChild(initialState)
	})

	beforeAll(() => {
		registerHotkeys()
	})

	beforeEach(() => {
		// Make sure the router is reset before each test
		goToRouteMock.mockClear()

		// Make sure the file is reset before each test
		file = new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/foobar.txt',
			owner: 'admin',
			mime: 'text/plain',
			permissions: Permission.ALL,
		})

		// Setting the view first as it reset the active node
		activeStore.onChangedView(view)
		activeStore.setActiveNode(file)

		window.OCA = { Files: { Sidebar: { open: () => {}, setActiveTab: () => {} } } }
		// We only mock what needed, we do not need Files.Router.goTo or Files.Navigation
		window.OCP = { Files: { Router: { goToRoute: goToRouteMock, params: {}, query: {} } } }

		initialState = document.createElement('input')
		initialState.setAttribute('type', 'hidden')
		initialState.setAttribute('id', 'initial-state-files_trashbin-config')
		initialState.setAttribute('value', btoa(JSON.stringify({
			allow_delete: true,
		})))
		document.body.appendChild(initialState)
	})

	it('Pressing d should open the sidebar once', () => {
		dispatchEvent({ key: 'd', code: 'KeyD' })

		// Modifier keys should not trigger the action
		dispatchEvent({ key: 'd', code: 'KeyD', ctrlKey: true })
		dispatchEvent({ key: 'd', code: 'KeyD', altKey: true })
		dispatchEvent({ key: 'd', code: 'KeyD', shiftKey: true })
		dispatchEvent({ key: 'd', code: 'KeyD', metaKey: true })

		expect(sidebarAction.enabled).toHaveReturnedWith(true)
		expect(sidebarAction.exec).toHaveBeenCalledOnce()
	})

	it('Pressing F2 should rename the file', () => {
		dispatchEvent({ key: 'F2', code: 'F2' })

		// Modifier keys should not trigger the action
		dispatchEvent({ key: 'F2', code: 'F2', ctrlKey: true })
		dispatchEvent({ key: 'F2', code: 'F2', altKey: true })
		dispatchEvent({ key: 'F2', code: 'F2', shiftKey: true })
		dispatchEvent({ key: 'F2', code: 'F2', metaKey: true })

		expect(renameAction.enabled).toHaveReturnedWith(true)
		expect(renameAction.exec).toHaveBeenCalledOnce()
	})

	it('Pressing s should toggle favorite', () => {
		vi.spyOn(axios, 'post').mockImplementationOnce(() => Promise.resolve())
		dispatchEvent({ key: 's', code: 'KeyS' })

		// Modifier keys should not trigger the action
		dispatchEvent({ key: 's', code: 'KeyS', ctrlKey: true })
		dispatchEvent({ key: 's', code: 'KeyS', altKey: true })
		dispatchEvent({ key: 's', code: 'KeyS', shiftKey: true })
		dispatchEvent({ key: 's', code: 'KeyS', metaKey: true })

		expect(favoriteAction.enabled).toHaveReturnedWith(true)
		expect(favoriteAction.exec).toHaveBeenCalledOnce()
	})

	it('Pressing Delete should delete the file', async () => {
		// @ts-expect-error unit testing
		vi.spyOn(deleteAction._action, 'exec').mockResolvedValue(() => true)

		dispatchEvent({ key: 'Delete', code: 'Delete' })

		// Modifier keys should not trigger the action
		dispatchEvent({ key: 'Delete', code: 'Delete', ctrlKey: true })
		dispatchEvent({ key: 'Delete', code: 'Delete', altKey: true })
		dispatchEvent({ key: 'Delete', code: 'Delete', shiftKey: true })
		dispatchEvent({ key: 'Delete', code: 'Delete', metaKey: true })

		expect(deleteAction.enabled).toHaveReturnedWith(true)
		expect(deleteAction.exec).toHaveBeenCalledOnce()
	})

	it('Pressing alt+up should go to parent directory', () => {
		expect(goToRouteMock).toHaveBeenCalledTimes(0)
		window.OCP.Files.Router.query = { dir: '/foo/bar' }

		dispatchEvent({ key: 'ArrowUp', code: 'ArrowUp', altKey: true })

		expect(goToRouteMock).toHaveBeenCalledOnce()
		expect(goToRouteMock.mock.calls[0][2].dir).toBe('/foo')
	})

	it('Pressing v should toggle grid view', async () => {
		vi.spyOn(axios, 'put').mockImplementationOnce(() => Promise.resolve())

		const userConfigStore = useUserConfigStore(getPinia())
		userConfigStore.userConfig.grid_view = false
		expect(userConfigStore.userConfig.grid_view).toBe(false)

		dispatchEvent({ key: 'v', code: 'KeyV' })
		expect(userConfigStore.userConfig.grid_view).toBe(true)
	})

	it.each([
		['ctrlKey'],
		['altKey'],
		// those meta keys are still triggering...
		// ['shiftKey'],
		// ['metaKey']
	])('Pressing v with modifier key %s should not toggle grid view', async (modifier: string) => {
		vi.spyOn(axios, 'put').mockImplementationOnce(() => Promise.resolve())

		const userConfigStore = useUserConfigStore(getPinia())
		userConfigStore.userConfig.grid_view = false
		expect(userConfigStore.userConfig.grid_view).toBe(false)

		dispatchEvent(new KeyboardEvent('keydown', { key: 'v', code: 'KeyV', [modifier]: true }))

		await nextTick()

		expect(userConfigStore.userConfig.grid_view).toBe(false)
	})
})

/**
 * Helper to dispatch the correct event.
 *
 * @param init - KeyboardEvent options
 */
function dispatchEvent(init: KeyboardEventInit) {
	document.body.dispatchEvent(new KeyboardEvent('keydown', { ...init, bubbles: true }))
}
