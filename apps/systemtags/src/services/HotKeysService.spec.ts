/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { describe, it, vi, expect, beforeEach, beforeAll } from 'vitest'
import { File, Permission, View } from '@nextcloud/files'

import { getPinia } from '../../../files/src/store/index.ts'
import { useActiveStore } from '../../../files/src/store/active.ts'

import { action as bulkSystemTagsAction } from '../files_actions/bulkSystemTagsAction.ts'
import { registerHotkeys } from './HotKeysService.ts'

let file: File
const view = {
	id: 'files',
	name: 'Files',
} as View

vi.mock('../files_actions/bulkSystemTagsAction.ts', { spy: true })

describe('HotKeysService testing', () => {
	const activeStore = useActiveStore(getPinia())

	beforeAll(() => {
		registerHotkeys()
	})

	beforeEach(() => {
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
	})

	it('Pressing t should open the tag management dialog', () => {
		dispatchEvent({ key: 't', code: 'KeyT' })

		// Modifier keys should not trigger the action
		dispatchEvent({ key: 't', code: 'KeyT', ctrlKey: true })
		dispatchEvent({ key: 't', code: 'KeyT', altKey: true })
		dispatchEvent({ key: 't', code: 'KeyT', shiftKey: true })
		dispatchEvent({ key: 't', code: 'KeyT', metaKey: true })

		expect(bulkSystemTagsAction.enabled).toHaveReturnedWith(true)
		expect(bulkSystemTagsAction.exec).toHaveBeenCalledOnce()
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
