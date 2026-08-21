/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { File, Folder, Permission } from '@nextcloud/files'
import { createTestingPinia } from '@pinia/testing'
import { shallowMount } from '@vue/test-utils'
import { beforeEach, describe, expect, test, vi } from 'vitest'
import { nextTick } from 'vue'
import FileEntryGrid from './FileEntryGrid.vue'
import router from '../router/router.ts'
import { useActiveStore } from '../store/active.ts'

// useFileListWidth builds its ResizeObserver while the module is evaluated, so
// the stub has to exist before the import chain runs. jsdom-testing-mocks sets
// its observer up from a hook, which is already too late here.
vi.hoisted(() => {
	globalThis.ResizeObserver = class {

		observe() {}
		unobserve() {}
		disconnect() {}

	}
})

vi.mock('@nextcloud/auth')

const source = new File({
	id: 42,
	source: 'http://nextcloud.local/remote.php/dav/files/test/Deep/Nested/report.pdf',
	root: '/files/test',
	owner: 'test',
	mime: 'application/pdf',
	permissions: Permission.READ,
})

const nestedFolder = new Folder({
	id: 41,
	source: 'http://nextcloud.local/remote.php/dav/files/test/Deep/Nested',
	root: '/files/test',
	owner: 'test',
	permissions: Permission.READ,
})

describe('FileEntryGrid.vue', () => {
	beforeEach(async () => {
		await router.replace({ name: 'filelist', params: { view: 'files' } })
	})

	// The grid entry hands its `activeFolder` to the file actions, so a stale copy
	// makes opening a file navigate to whichever folder was active when the entry
	// was first rendered rather than the one the file lives in.
	test('follows the active folder after navigating', async () => {
		const wrapper = shallowMount(FileEntryGrid, {
			propsData: { source, nodes: [source] },
			mocks: { t: (_: string, text: string) => text },
			router,
			pinia: createTestingPinia({ createSpy: vi.fn }),
		})
		const activeStore = useActiveStore()

		expect(wrapper.vm.activeFolder).not.toBe(nestedFolder)

		activeStore.activeFolder = nestedFolder
		await nextTick()

		expect(wrapper.vm.activeFolder).toBe(nestedFolder)
	})
})
