/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFileListHeader, IFolder } from '@nextcloud/files'

import { registerFileListHeader } from '@nextcloud/files'
import { describe, expect, it, vi } from 'vitest'
import { nextTick } from 'vue'
import registerNoteToRecipient from './noteToRecipient.ts'

vi.mock('@nextcloud/files', async (importOriginal) => ({
	...await importOriginal(),
	registerFileListHeader: vi.fn(),
}))

/**
 * @param note The note of the folder
 */
function folder(note: string) {
	return { attributes: { note }, owner: 'alice' } as unknown as IFolder
}

describe('registerNoteToRecipient', () => {
	it('renders the note of the folder and follows folder updates', async () => {
		registerNoteToRecipient()
		const header = vi.mocked(registerFileListHeader).mock.calls[0]![0] as IFileListHeader
		const el = document.createElement('div')

		await header.render(el, folder('Please sign the documents'), {} as never)
		await nextTick()
		expect(el.textContent).toContain('Please sign the documents')

		header.updated(folder('Thank you'), {} as never)
		await nextTick()
		expect(el.textContent).toContain('Thank you')
	})
})
