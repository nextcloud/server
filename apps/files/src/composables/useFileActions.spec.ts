/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFileAction, INode, registerFileAction } from '@nextcloud/files'
import type * as composable from './useFileActions.ts'

import { Folder, View } from '@nextcloud/files'
import { defaultRemoteURL, defaultRootPath } from '@nextcloud/files/dav'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { nextTick, ref } from 'vue'

interface Context {
	useFileActions: typeof composable.useFileActions
	useEnabledFileActions: typeof composable.useEnabledFileActions
	registerFileAction: typeof registerFileAction
}

describe('useFileActions', () => {
	beforeEach(async (context: Context) => {
		delete globalThis._nc_files_scope
		// reset modules to reset internal variables (the headers ref) of the composable and the library (the scoped globals)
		vi.resetModules()
		context.useFileActions = (await import('./useFileActions.ts')).useFileActions
		context.useEnabledFileActions = (await import('./useFileActions.ts')).useEnabledFileActions
		context.registerFileAction = (await import('@nextcloud/files')).registerFileAction
	})

	it<Context>('gets the actions', ({ useFileActions, registerFileAction }) => {
		const action: IFileAction = { id: '1', order: 5, displayName: () => 'Action', iconSvgInline: vi.fn(), exec: vi.fn() }
		registerFileAction(action)

		const actions = useFileActions()
		expect(actions.value).toEqual([action])
	})

	it<Context>('composable is reactive', async ({ useFileActions, registerFileAction }) => {
		const action: IFileAction = { id: '1', order: 5, displayName: () => 'Action', iconSvgInline: vi.fn(), exec: vi.fn() }
		registerFileAction(action)
		await nextTick()

		const actions = useFileActions()
		expect(actions.value.map(({ id }) => id)).toStrictEqual(['1'])
		// now add a new action
		const action2: IFileAction = { id: '2', order: 9, displayName: () => 'Action', iconSvgInline: vi.fn(), exec: vi.fn() }
		registerFileAction(action2)

		// reactive update, lower order first
		await nextTick()
		expect(actions.value.map(({ id }) => id)).toStrictEqual(['1', '2'])
	})
})

describe('useEnabledFileActions', () => {
	beforeEach(async (context: Context) => {
		delete globalThis._nc_files_scope
		// reset modules to reset internal variables (the headers ref) of the composable and the library (the scoped globals)
		vi.resetModules()
		context.useFileActions = (await import('./useFileActions.ts')).useFileActions
		context.useEnabledFileActions = (await import('./useFileActions.ts')).useEnabledFileActions
		context.registerFileAction = (await import('@nextcloud/files')).registerFileAction
	})

	it<Context>('gets the actions', ({ useEnabledFileActions, registerFileAction }) => {
		registerFileAction({ id: '1', order: 0, displayName: () => 'Action 1', iconSvgInline: vi.fn(), exec: vi.fn() })
		registerFileAction({ id: '2', order: 5, displayName: () => 'Action 2', enabled: () => false, iconSvgInline: vi.fn(), exec: vi.fn() })
		registerFileAction({ id: '3', order: 9, displayName: () => 'Action 3', enabled: () => true, iconSvgInline: vi.fn(), exec: vi.fn() })

		const folder = new Folder({ owner: 'owner', root: defaultRootPath, source: defaultRemoteURL + defaultRootPath })
		const view = new View({ id: 'view', getContents: vi.fn(), icon: '<svg></svg>', name: 'View' })
		const contents = []
		const actions = useEnabledFileActions({ folder, contents, view })
		expect(actions.value.map(({ id }) => id)).toStrictEqual(['1', '3'])
	})

	it<Context>('composable is reactive', async ({ useEnabledFileActions, registerFileAction }) => {
		registerFileAction({ id: '1', order: 0, displayName: () => 'Action 1', iconSvgInline: vi.fn(), exec: vi.fn() })
		registerFileAction({ id: '2', order: 5, displayName: () => 'Action 2', enabled: () => false, iconSvgInline: vi.fn(), exec: vi.fn() })

		const folder = new Folder({ owner: 'owner', root: defaultRootPath, source: defaultRemoteURL + defaultRootPath })
		const view = new View({ id: 'view', getContents: vi.fn(), icon: '<svg></svg>', name: 'View' })
		const contents = []
		const actions = useEnabledFileActions({ folder, contents, view })
		expect(actions.value.map(({ id }) => id)).toStrictEqual(['1'])

		registerFileAction({ id: '3', order: 9, displayName: () => 'Action 3', enabled: () => true, iconSvgInline: vi.fn(), exec: vi.fn() })
		expect(actions.value.map(({ id }) => id)).toStrictEqual(['1', '3'])
	})

	it<Context>('composable is reactive to context changes', async ({ useEnabledFileActions, registerFileAction }) => {
		// only enabled if view id === 'enabled-view'
		registerFileAction({ id: '1', order: 0, displayName: () => 'Action 1', enabled: ({ view }) => view.id === 'enabled-view', iconSvgInline: vi.fn(), exec: vi.fn() })
		// only enabled if contents has items
		registerFileAction({ id: '2', order: 5, displayName: () => 'Action 2', enabled: ({ contents }) => contents.length > 0, iconSvgInline: vi.fn(), exec: vi.fn() })
		// only enabled if folder owner is 'owner2'
		registerFileAction({ id: '3', order: 9, displayName: () => 'Action 3', enabled: ({ folder }) => folder.owner === 'owner2', iconSvgInline: vi.fn(), exec: vi.fn() })

		const context = ref({
			folder: new Folder({ owner: 'owner', root: defaultRootPath, source: defaultRemoteURL + defaultRootPath }),
			view: new View({ id: 'disabled-view', getContents: vi.fn(), icon: '<svg></svg>', name: 'View' }),
			contents: ref<INode[]>([(new Folder({ owner: 'owner', root: defaultRootPath, source: defaultRemoteURL + defaultRootPath }))]),
		})
		const actions = useEnabledFileActions(context)

		// we have contents but wrong folder and view so only 2 is enabled
		expect(actions.value.map(({ id }) => id)).toStrictEqual(['2'])

		// no contents so nothing is enabled
		context.value.contents = []
		await nextTick()
		expect(actions.value.map(({ id }) => id)).toStrictEqual([])

		// correct owner for action 3
		context.value.folder = new Folder({ owner: 'owner2', root: defaultRootPath, source: defaultRemoteURL + defaultRootPath })
		await nextTick()
		expect(actions.value.map(({ id }) => id)).toStrictEqual(['3'])

		// correct view for action 1
		context.value.view = new View({ id: 'enabled-view', getContents: vi.fn(), icon: '<svg></svg>', name: 'View' })
		await nextTick()
		expect(actions.value.map(({ id }) => id)).toStrictEqual(['1', '3'])
	})
})
