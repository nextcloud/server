/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { IFile, IFolder, IView } from '@nextcloud/files'
import type { MockedObject } from 'vitest'
import type { Viewer } from '../../src/api_package/viewer.ts'
import type * as HistoryModule from '../../src/utils/history.ts'

import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { makeFile } from '../factories.ts'

vi.mock('../../src/api_package/viewer.ts')

const view = { id: 'files' } as IView
const folder = { path: '/photos' } as IFolder

let openWithHistory: typeof HistoryModule.openWithHistory
let viewer: MockedObject<Viewer>
let addSpy: ReturnType<typeof vi.spyOn>
let removeSpy: ReturnType<typeof vi.spyOn>
let goSpy: ReturnType<typeof vi.spyOn>

/**
 * Set the fake Files router on the window.
 *
 * @param query - The initial router query
 * @param params - The initial router params
 */
function setRouter(query: Record<string, string> = {}, params: Record<string, string> = {}) {
	const router = { params, query, goToRoute: vi.fn() }
	window.OCP = { Files: { Router: router } } as never
	return router
}

/**
 * Get the popstate handler registered by the module through addEventListener.
 */
function popstateHandler(addSpy: ReturnType<typeof vi.spyOn>): () => void {
	const call = addSpy.mock.calls.find((c) => c[0] === 'popstate')
	return call![1] as () => void
}

/**
 * Get the options passed to the mocked viewer.open call.
 */
function openOptions(): { onNext: (f: IFile) => void, onPrev: (f: IFile) => void, onClose: () => void } {
	return viewer.open.mock.calls[0]![2] as never
}

describe('openWithHistory', () => {
	beforeEach(async () => {
		vi.resetModules()
		// Re-import the mocked viewer AFTER resetModules so this spec and the
		// history module under test share the same mock instance.
		const { getViewer } = await import('../../src/api_package/viewer.ts')
		viewer = vi.mocked(getViewer())
		// Reset the per-entry viewer offset kept in history state.
		window.history.replaceState({}, '')
		addSpy = vi.spyOn(window, 'addEventListener')
		removeSpy = vi.spyOn(window, 'removeEventListener')
		goSpy = vi.spyOn(window.history, 'go').mockImplementation(() => {})
		;({ openWithHistory } = await import('../../src/utils/history.ts'))
	})

	afterEach(() => {
		vi.restoreAllMocks()
		window.OCP = {} as never
	})

	it('opens without history integration when there is no router', () => {
		window.OCP = {} as never
		const file = makeFile({ id: 1 })
		openWithHistory([file], file, view, folder)
		expect(viewer.open).toHaveBeenCalledWith([file], file, { view, folder }, undefined)
		expect(addSpy).not.toHaveBeenCalledWith('popstate', expect.anything())
	})

	it('opens without history integration when view or folder is missing', () => {
		setRouter()
		const file = makeFile({ id: 1 })
		openWithHistory([file], file, undefined, folder)
		expect(viewer.open).toHaveBeenCalledWith([file], file, { view: undefined, folder }, undefined)
		expect(addSpy).not.toHaveBeenCalledWith('popstate', expect.anything())
	})

	it('pushes a history entry and wires navigation callbacks on a fresh open', () => {
		const router = setRouter()
		const file = makeFile({ id: 42 })
		openWithHistory([file], file, view, folder)

		// The opened file gets its own history entry, tagged with an offset.
		expect(router.goToRoute).toHaveBeenCalledWith(
			'filelist',
			expect.objectContaining({ view: 'files', fileid: '42' }),
			expect.objectContaining({ dir: '/photos', openfile: 'true' }),
			false,
		)
		expect(window.history.state?.viewerPos).toBe(1)
		expect(addSpy).toHaveBeenCalledWith('popstate', expect.any(Function))

		router.goToRoute.mockClear()
		openOptions().onNext(makeFile({ id: 43 }))
		expect(router.goToRoute).toHaveBeenCalledWith(
			'filelist',
			expect.objectContaining({ fileid: '43' }),
			expect.objectContaining({ openfile: 'true' }),
			false,
		)
		expect(window.history.state?.viewerPos).toBe(2)
	})

	it('does not push an entry when opened from an openfile URL (refresh)', () => {
		const router = setRouter({ openfile: 'true' })
		const file = makeFile({ id: 7 })
		openWithHistory([file], file, view, folder)
		expect(router.goToRoute).not.toHaveBeenCalled()
	})

	it('unwinds every pushed entry when closed from within the viewer', () => {
		const router = setRouter()
		const file = makeFile({ id: 1 })
		openWithHistory([file], file, view, folder)
		// One navigation → two entries pushed in total.
		openOptions().onNext(makeFile({ id: 2 }))

		router.query.openfile = 'true'
		openOptions().onClose()

		expect(goSpy).toHaveBeenCalledWith(-2)
		expect(removeSpy).toHaveBeenCalledWith('popstate', expect.any(Function))
	})

	it('drops the openfile flag in place when closing a refresh-opened viewer', () => {
		const router = setRouter({ openfile: 'true', dir: '/photos' })
		const file = makeFile({ id: 1 })
		openWithHistory([file], file, view, folder)

		openOptions().onClose()

		expect(goSpy).not.toHaveBeenCalled()
		expect(router.goToRoute).toHaveBeenCalledWith(
			'filelist',
			router.params,
			expect.not.objectContaining({ openfile: 'true' }),
			true,
		)
	})

	it('moves the viewer to the file in the URL on back/forward', () => {
		const router = setRouter()
		const file = makeFile({ id: 1 })
		openWithHistory([file], file, view, folder)

		router.query.openfile = 'true'
		router.params.fileid = '99'
		popstateHandler(addSpy)()
		expect(viewer.goTo).toHaveBeenCalledWith(99)
		expect(viewer.close).not.toHaveBeenCalled()
	})

	it('opens in editing mode from an editing=true URL (refresh/deeplink)', () => {
		setRouter({ openfile: 'true', editing: 'true' })
		const file = makeFile({ id: 1 })
		openWithHistory([file], file, view, folder)
		const options = viewer.open.mock.calls[0]![2] as { editing: boolean }
		expect(options.editing).toBe(true)
	})

	it('ignores a stale editing param on a fresh open (no openfile)', () => {
		const router = setRouter({ editing: 'true' })
		const file = makeFile({ id: 42 })
		openWithHistory([file], file, view, folder)

		const options = viewer.open.mock.calls[0]![2] as { editing: boolean }
		expect(options.editing).toBe(false)
		// The pushed entry must not carry the stale editing flag either.
		expect(router.goToRoute).toHaveBeenCalledWith(
			'filelist',
			expect.anything(),
			expect.not.objectContaining({ editing: 'true' }),
			false,
		)
	})

	it('syncs the editing state from the URL on back/forward', () => {
		const router = setRouter()
		const file = makeFile({ id: 1 })
		openWithHistory([file], file, view, folder)

		router.query.openfile = 'true'
		router.query.editing = 'true'
		router.params.fileid = '1'
		popstateHandler(addSpy)()
		expect(viewer.setEditing).toHaveBeenCalledWith(true)
	})

	it('reflects an editing change in the URL by replacing the current entry', () => {
		const router = setRouter()
		const file = makeFile({ id: 1 })
		openWithHistory([file], file, view, folder)

		const options = viewer.open.mock.calls[0]![2] as { onEditingChange: (editing: boolean) => void }
		router.goToRoute.mockClear()
		options.onEditingChange(true)
		expect(router.goToRoute).toHaveBeenCalledWith(
			'filelist',
			router.params,
			expect.objectContaining({ editing: 'true' }),
			true,
		)
	})

	it('closes the viewer when navigating out of the openfile range', () => {
		const router = setRouter()
		const file = makeFile({ id: 1 })
		openWithHistory([file], file, view, folder)

		delete router.query.openfile
		popstateHandler(addSpy)()
		expect(viewer.close).toHaveBeenCalled()
		expect(removeSpy).toHaveBeenCalledWith('popstate', expect.any(Function))
	})
})
