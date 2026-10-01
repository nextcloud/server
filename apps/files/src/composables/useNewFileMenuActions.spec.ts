/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { addNewFileMenuEntry, INode } from '@nextcloud/files'
import type * as composable from './useNewFileMenuActions.ts'

import { File, Folder, NewMenuEntryCategory, Permission } from '@nextcloud/files'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'

interface Context {
	useNewFileMenuActions: typeof composable.useNewFileMenuActions
	addNewFileMenuEntry: typeof addNewFileMenuEntry
}

const folder = new Folder({
	id: 1,
	owner: 'admin',
	permissions: Permission.ALL,
	root: '/files/admin',
	source: 'http://nextcloud.local/remote.php/dav/files/admin/folder',
})

describe('useNewFileMenuActions', () => {
	beforeEach(async (context: Context) => {
		delete globalThis._nc_files_scope
		// reset modules to reset the scoped globals of the library (the new file menu)
		vi.resetModules()
		context.useNewFileMenuActions = (await import('./useNewFileMenuActions.ts')).useNewFileMenuActions
		context.addNewFileMenuEntry = (await import('@nextcloud/files')).addNewFileMenuEntry
	})

	it<Context>('has no actions without a folder', ({ useNewFileMenuActions, addNewFileMenuEntry }) => {
		addNewFileMenuEntry({ id: 'new', displayName: 'New', iconSvgInline: '<svg />', handler: vi.fn() })

		expect(useNewFileMenuActions(ref(undefined), ref([])).value).toStrictEqual([])
	})

	it<Context>('groups the entries by category', ({ useNewFileMenuActions, addNewFileMenuEntry }) => {
		addNewFileMenuEntry({ id: 'other', category: NewMenuEntryCategory.Other, displayName: 'Other', iconSvgInline: '<svg />', handler: vi.fn() })
		addNewFileMenuEntry({ id: 'upload', category: NewMenuEntryCategory.UploadFromDevice, displayName: 'Upload', iconSvgInline: '<svg />', handler: vi.fn() })
		// entries without a category default to "create new"
		addNewFileMenuEntry({ id: 'new', displayName: 'New', iconSvgInline: '<svg />', handler: vi.fn() })

		const actions = useNewFileMenuActions(ref(folder), ref([]))
		expect(actions.value.map(({ caption, actions }) => [caption, actions.map(({ label }) => label)]))
			.toStrictEqual([
				['Upload from device', ['Upload']],
				['Create new', ['New']],
				['Other', ['Other']],
			])
	})

	it<Context>('skips empty groups', ({ useNewFileMenuActions, addNewFileMenuEntry }) => {
		addNewFileMenuEntry({ id: 'new', displayName: 'New', iconSvgInline: '<svg />', handler: vi.fn() })

		const actions = useNewFileMenuActions(ref(folder), ref([]))
		expect(actions.value.map(({ caption }) => caption)).toStrictEqual(['Create new'])
	})

	it<Context>('entries are sorted and filtered by the entry conditions', ({ useNewFileMenuActions, addNewFileMenuEntry }) => {
		addNewFileMenuEntry({ id: 'second', order: 2, displayName: 'Second', iconSvgInline: '<svg />', handler: vi.fn() })
		addNewFileMenuEntry({ id: 'first', order: 1, displayName: 'First', iconSvgInline: '<svg />', handler: vi.fn() })
		addNewFileMenuEntry({ id: 'disabled', displayName: 'Disabled', iconSvgInline: '<svg />', enabled: () => false, handler: vi.fn() })

		const actions = useNewFileMenuActions(ref(folder), ref([]))
		expect(actions.value[0]!.actions.map(({ label }) => label)).toStrictEqual(['First', 'Second'])
	})

	it<Context>('provides the icon of an entry', ({ useNewFileMenuActions, addNewFileMenuEntry }) => {
		addNewFileMenuEntry({ id: 'icon', displayName: 'With icon', iconSvgInline: '<svg id="icon" />', handler: vi.fn() })

		const actions = useNewFileMenuActions(ref(folder), ref([]))
		expect(actions.value[0]!.actions.map(({ iconSvg }) => iconSvg)).toStrictEqual(['<svg id="icon" />'])
	})

	it<Context>('calls the entry handler with the current folder and its contents', ({ useNewFileMenuActions, addNewFileMenuEntry }) => {
		const handler = vi.fn()
		addNewFileMenuEntry({ id: 'new', displayName: 'New', iconSvgInline: '<svg />', handler })

		const contents = ref<INode[]>([])
		const actions = useNewFileMenuActions(ref(folder), contents)

		// the contents are resolved on click, so also nodes added after the menu was built are passed
		const node = new File({ mime: 'text/plain', owner: 'admin', root: '/files/admin', source: `${folder.source}/file.txt` })
		contents.value = [node]

		actions.value[0]!.actions[0]!.onClick()
		expect(handler).toHaveBeenCalledWith(folder, [node])
	})
})
