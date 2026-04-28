/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'

const getUploader = vi.hoisted(() => vi.fn())
const t = vi.hoisted(() => vi.fn())

vi.mock('@nextcloud/upload', () => ({
	getUploader,
	UploaderStatus: {
		IDLE: 0,
		UPLOADING: 1,
		PAUSED: 2,
	},
}))

vi.mock('@nextcloud/l10n', () => ({
	t,
}))

describe('registerUploadBeforeUnload', () => {
	beforeEach(() => {
		vi.resetModules()
		vi.clearAllMocks()
	})

	async function getBeforeUnloadHandler(): Promise<(event: BeforeUnloadEvent) => void> {
		const addListener = vi.spyOn(window, 'addEventListener')
		const { default: register } = await import('./UploadBeforeUnload.ts')
		register()
		const call = addListener.mock.calls.find((c) => c[0] === 'beforeunload')
		expect(call).toBeDefined()
		addListener.mockRestore()
		return call![1] as (event: BeforeUnloadEvent) => void
	}

	it('does not prevent unload or translate when uploader is idle', async () => {
		getUploader.mockReturnValue({ info: { status: 0 } })
		const handler = await getBeforeUnloadHandler()
		const event = new Event('beforeunload') as BeforeUnloadEvent
		const preventDefault = vi.spyOn(event, 'preventDefault')
		handler(event)
		expect(preventDefault).not.toHaveBeenCalled()
		expect(t).not.toHaveBeenCalled()
	})

	it.each([
		['uploading', 1],
		['paused', 2],
	] as const)('sets leave warning when uploader is %s', async (_label, status) => {
		t.mockReturnValue('leave-warning')
		getUploader.mockReturnValue({ info: { status } })
		const handler = await getBeforeUnloadHandler()
		const event = new Event('beforeunload') as BeforeUnloadEvent
		const preventDefault = vi.spyOn(event, 'preventDefault')
		handler(event)
		expect(preventDefault).toHaveBeenCalled()
		expect(t).toHaveBeenCalledWith(
			'files',
			'File uploads are still in progress. Leaving the page will cancel them.',
		)
	})

	it('adds only one beforeunload listener when register is called multiple times', async () => {
		getUploader.mockReturnValue({ info: { status: 0 } })
		const addListener = vi.spyOn(window, 'addEventListener')
		const { default: register } = await import('./UploadBeforeUnload.ts')
		register()
		register()
		register()
		const beforeUnloadCalls = addListener.mock.calls.filter((c) => c[0] === 'beforeunload')
		expect(beforeUnloadCalls).toHaveLength(1)
		addListener.mockRestore()
	})
})
