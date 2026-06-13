/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFileListHeader } from '@nextcloud/files'
import type { registerFileListHeader } from '@nextcloud/files'
import type { ComputedRef } from 'vue'

import { beforeEach, describe, expect, it, vi } from 'vitest'
import { nextTick } from 'vue'

interface Context {
	useFileListHeaders: () => ComputedRef<IFileListHeader[]>
	registerFileListHeader: typeof registerFileListHeader
}

describe('useFileListHeaders', () => {
	beforeEach(async (context: Context) => {
		delete globalThis._nc_files_scope
		// reset modules to reset internal variables (the headers ref) of the composable and the library (the scoped globals)
		vi.resetModules()
		context.useFileListHeaders = (await import('./useFileListHeaders.ts')).useFileListHeaders
		context.registerFileListHeader = (await import('@nextcloud/files')).registerFileListHeader
	})

	it<Context>('gets the headers', ({ useFileListHeaders, registerFileListHeader }) => {
		const header: IFileListHeader = { id: '1', order: 5, render: vi.fn(), updated: vi.fn() }
		registerFileListHeader(header)

		const headers = useFileListHeaders()
		expect(headers.value).toEqual([header])
	})

	it<Context>('headers are sorted', ({ useFileListHeaders, registerFileListHeader }) => {
		const header: IFileListHeader = { id: '1', order: 10, render: vi.fn(), updated: vi.fn() }
		const header2: IFileListHeader = { id: '2', order: 5, render: vi.fn(), updated: vi.fn() }
		registerFileListHeader(header)
		registerFileListHeader(header2)

		const headers = useFileListHeaders()
		// lower order first
		expect(headers.value.map(({ id }) => id)).toStrictEqual(['2', '1'])
	})

	it<Context>('composable is reactive', async ({ useFileListHeaders, registerFileListHeader }) => {
		const header: IFileListHeader = { id: 'a', order: 10, render: vi.fn(), updated: vi.fn() }
		registerFileListHeader(header)
		await nextTick()

		const headers = useFileListHeaders()
		expect(headers.value.map(({ id }) => id)).toStrictEqual(['a'])
		// now add a new header
		const header2: IFileListHeader = { id: 'b', order: 5, render: vi.fn(), updated: vi.fn() }
		registerFileListHeader(header2)

		// reactive update, lower order first
		await nextTick()
		expect(headers.value.map(({ id }) => id)).toStrictEqual(['b', 'a'])
	})
})
