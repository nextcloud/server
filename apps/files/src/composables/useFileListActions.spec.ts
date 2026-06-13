/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFileListAction, INode, registerFileListAction } from '@nextcloud/files'
import type * as composable from './useFileListActions.ts'

import { Folder, View } from '@nextcloud/files'
import { defaultRemoteURL, defaultRootPath } from '@nextcloud/files/dav'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { nextTick, ref, shallowRef } from 'vue'

interface Context {
	useFileListActions: typeof composable.useFileListActions
	useEnabledFileListActions: typeof composable.useEnabledFileListActions
	registerFileListAction: typeof registerFileListAction
}

describe('useFileListActions', () => {
	beforeEach(async (context: Context) => {
		delete globalThis._nc_files_scope
		// reset modules to reset internal variables (the headers ref) of the composable and the library (the scoped globals)
		vi.resetModules()
		context.useFileListActions = (await import('./useFileListActions.ts')).useFileListActions
		context.useEnabledFileListActions = (await import('./useFileListActions.ts')).useEnabledFileListActions
		context.registerFileListAction = (await import('@nextcloud/files')).registerFileListAction
	})

	it<Context>('gets the actions', ({ useFileListActions, registerFileListAction }) => {
		const action: IFileListAction = { id: '1', order: 5, displayName: () => 'Action', exec: vi.fn() }
		registerFileListAction(action)

		const actions = useFileListActions()
		expect(actions.value).toEqual([action])
	})

	it<Context>('actions are sorted', ({ useFileListActions, registerFileListAction }) => {
		const action: IFileListAction = { id: '1', order: 5, displayName: () => 'Action 1', exec: vi.fn() }
		const action2: IFileListAction = { id: '2', order: 0, displayName: () => 'Action 2', exec: vi.fn() }
		registerFileListAction(action)
		registerFileListAction(action2)

		const actions = useFileListActions()
		// lower order first
		expect(actions.value.map(({ id }) => id)).toStrictEqual(['2', '1'])
	})

	it<Context>('composable is reactive', async ({ useFileListActions, registerFileListAction }) => {
		const action: IFileListAction = { id: '1', order: 5, displayName: () => 'Action', exec: vi.fn() }
		registerFileListAction(action)
		await nextTick()

		const actions = useFileListActions()
		expect(actions.value.map(({ id }) => id)).toStrictEqual(['1'])
		// now add a new action
		const action2: IFileListAction = { id: '2', order: 0, displayName: () => 'Action', exec: vi.fn() }
		registerFileListAction(action2)

		// reactive update, lower order first
		await nextTick()
		expect(actions.value.map(({ id }) => id)).toStrictEqual(['2', '1'])
	})
})

describe('useEnabledFileListActions', () => {
	beforeEach(async (context: Context) => {
		delete globalThis._nc_files_scope
		// reset modules to reset internal variables (the headers ref) of the composable and the library (the scoped globals)
		vi.resetModules()
		context.useFileListActions = (await import('./useFileListActions.ts')).useFileListActions
		context.useEnabledFileListActions = (await import('./useFileListActions.ts')).useEnabledFileListActions
		context.registerFileListAction = (await import('@nextcloud/files')).registerFileListAction
	})

	it<Context>('gets the actions sorted', ({ useEnabledFileListActions, registerFileListAction }) => {
		registerFileListAction({ id: '1', order: 0, displayName: () => 'Action 1', exec: vi.fn() })
		registerFileListAction({ id: '2', order: 5, displayName: () => 'Action 2', enabled: () => false, exec: vi.fn() })
		registerFileListAction({ id: '3', order: 9, displayName: () => 'Action 3', enabled: () => true, exec: vi.fn() })

		const folder = new Folder({ owner: 'owner', root: defaultRootPath, source: defaultRemoteURL + defaultRootPath })
		const view = new View({ id: 'view', getContents: vi.fn(), icon: '<svg></svg>', name: 'View' })
		const contents = []
		const actions = useEnabledFileListActions(folder, contents, view)
		expect(actions.value.map(({ id }) => id)).toStrictEqual(['1', '3'])
	})

	it<Context>('composable is reactive', async ({ useEnabledFileListActions, registerFileListAction }) => {
		registerFileListAction({ id: '1', order: 0, displayName: () => 'Action 1', exec: vi.fn() })
		registerFileListAction({ id: '2', order: 5, displayName: () => 'Action 2', enabled: () => false, exec: vi.fn() })

		const folder = new Folder({ owner: 'owner', root: defaultRootPath, source: defaultRemoteURL + defaultRootPath })
		const view = new View({ id: 'view', getContents: vi.fn(), icon: '<svg></svg>', name: 'View' })
		const contents = []
		const actions = useEnabledFileListActions(folder, contents, view)
		expect(actions.value.map(({ id }) => id)).toStrictEqual(['1'])

		registerFileListAction({ id: '3', order: 9, displayName: () => 'Action 3', enabled: () => true, exec: vi.fn() })
		expect(actions.value.map(({ id }) => id)).toStrictEqual(['1', '3'])
	})

	it<Context>('composable is reactive to context changes', async ({ useEnabledFileListActions, registerFileListAction }) => {
		// only enabled if view id === 'enabled-view'
		registerFileListAction({ id: '1', order: 0, displayName: () => 'Action 1', enabled: ({ view }) => view.id === 'enabled-view', exec: vi.fn() })
		// only enabled if contents has items
		registerFileListAction({ id: '2', order: 5, displayName: () => 'Action 2', enabled: ({ contents }) => contents.length > 0, exec: vi.fn() })
		// only enabled if folder owner is 'owner2'
		registerFileListAction({ id: '3', order: 9, displayName: () => 'Action 3', enabled: ({ folder }) => folder.owner === 'owner2', exec: vi.fn() })

		const folder = shallowRef(new Folder({ owner: 'owner', root: defaultRootPath, source: defaultRemoteURL + defaultRootPath }))
		const view = shallowRef(new View({ id: 'disabled-view', getContents: vi.fn(), icon: '<svg></svg>', name: 'View' }))
		const contents = ref<INode[]>([folder.value])
		const actions = useEnabledFileListActions(folder, contents, view)

		// we have contents but wrong folder and view so only 2 is enabled
		expect(actions.value.map(({ id }) => id)).toStrictEqual(['2'])

		// no contents so nothing is enabled
		contents.value = []
		await nextTick()
		expect(actions.value.map(({ id }) => id)).toStrictEqual([])

		// correct owner for action 3
		folder.value = new Folder({ owner: 'owner2', root: defaultRootPath, source: defaultRemoteURL + defaultRootPath })
		await nextTick()
		expect(actions.value.map(({ id }) => id)).toStrictEqual(['3'])

		// correct view for action 1
		view.value = new View({ id: 'enabled-view', getContents: vi.fn(), icon: '<svg></svg>', name: 'View' })
		await nextTick()
		expect(actions.value.map(({ id }) => id)).toStrictEqual(['1', '3'])
	})
})
