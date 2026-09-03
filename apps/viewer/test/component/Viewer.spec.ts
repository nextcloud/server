import { flushPromises } from '@vue/test-utils'
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { afterEach, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest'

// Mock the event bus BEFORE importing the component (shared manual mock).
vi.mock('@nextcloud/event-bus')
// Avoid the real DAV client being created on module import.
vi.mock('../../src/services/dav.ts', () => ({ fetchFolderContent: vi.fn(async () => []) }))

import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import { registerFileAction } from '@nextcloud/files'
import { makeFile, makeHandler } from '../factories.ts'
import { mountViewer } from './mountViewer.ts'

function imageHandler() {
	return makeHandler({
		id: 'image',
		tagname: 'oca-viewer-image',
		group: 'media',
		enabled: (nodes) => nodes.every((n) => n.mime?.startsWith('image/')),
	})
}

beforeEach(() => {
	// Call history is cleared globally in test/setup.ts (vi.clearAllMocks()).
})

afterEach(() => {
	document.body.innerHTML = ''
	document.body.className = ''
})

describe('Viewer.open()', () => {
	it('activates the matching handler and shows the modal', async () => {
		const { vm, wrapper, modalHandlerId, modalName, modalProps } = mountViewer([imageHandler()])
		const f1 = makeFile({ basename: 'a.jpg', mime: 'image/jpeg' })

		await vm.open([f1], f1)
		await wrapper.vm.$nextTick()

		expect(modalHandlerId()).toBe('image')
		expect(modalName()).toBe('a.jpg')
		expect(modalProps().show).toBe(true)
	})

	it('filters currentFileList to files of the same handler group', async () => {
		const pdfHandler = makeHandler({
			id: 'pdf',
			tagname: 'oca-viewer-pdf',
			group: 'documents',
			enabled: (nodes) => nodes.every((n) => n.mime === 'application/pdf'),
		})
		const { vm, wrapper, modalName, modalProps, emitModal } = mountViewer([imageHandler(), pdfHandler])
		const img1 = makeFile({ basename: 'img1.jpg', mime: 'image/jpeg' })
		const img2 = makeFile({ basename: 'img2.jpg', mime: 'image/jpeg' })
		const doc = makeFile({ basename: 'doc.pdf', mime: 'application/pdf' })

		// Open the image; the pdf is a different group and must be filtered out.
		await vm.open([img1, img2, doc], img1, { canLoop: false })
		await wrapper.vm.$nextTick()

		expect(modalName()).toBe('img1.jpg')
		expect(modalProps().hasNext).toBe(true)

		await emitModal('next')
		expect(modalName()).toBe('img2.jpg')
		// At the end of the filtered [img1, img2] list, with canLoop=false → no next.
		expect(modalProps().hasNext).toBe(false)

		// The pdf, being of another group, is never reachable through navigation.
		await emitModal('next')
		expect(modalName()).toBe('img2.jpg')
	})

	it('shows an error when the explicit handlerId is not registered', async () => {
		const { vm, wrapper, errorText, modalProps } = mountViewer([imageHandler()])
		const f1 = makeFile({ mime: 'image/jpeg' })

		await vm.open([f1], f1, undefined, 'does-not-exist')
		await wrapper.vm.$nextTick()

		expect(errorText()).toBe('There was no plugin available to open this file.')
		// No file got opened.
		expect(modalProps().show).toBe(false)
	})

	it('does not throw for either backdrop theme', async () => {
		const light = makeHandler({ id: 'light', tagname: 'oca-viewer-light', theme: 'light', enabled: () => true })
		const { vm, wrapper, modalHandlerId } = mountViewer([light])
		const f1 = makeFile()
		await expect(vm.open([f1], f1)).resolves.not.toThrow()
		await wrapper.vm.$nextTick()
		expect(modalHandlerId()).toBe('light')

		const dark = makeHandler({ id: 'dark', tagname: 'oca-viewer-dark', theme: 'dark', enabled: () => true })
		const second = mountViewer([dark])
		const f2 = makeFile()
		await expect(second.vm.open([f2], f2)).resolves.not.toThrow()
	})
})

describe('Viewer navigation', () => {
	const setup = async (canLoop: boolean) => {
		const onNext = vi.fn()
		const onPrev = vi.fn()
		const onClose = vi.fn()
		const loadMore = vi.fn(async () => [])
		const ctx = mountViewer([imageHandler()])
		const f1 = makeFile({ basename: 'f1.jpg', mime: 'image/jpeg' })
		const f2 = makeFile({ basename: 'f2.jpg', mime: 'image/jpeg' })
		const f3 = makeFile({ basename: 'f3.jpg', mime: 'image/jpeg' })
		await ctx.vm.open([f1, f2, f3], f1, { onNext, onPrev, onClose, canLoop, loadMore })
		await ctx.wrapper.vm.$nextTick()
		return { ...ctx, onNext, onPrev, onClose, loadMore, f1, f2, f3 }
	}

	it('next advances the current file and calls onNext with the new file', async () => {
		const { emitModal, modalName, onNext, f2 } = await setup(true)
		await emitModal('next')
		expect(modalName()).toBe('f2.jpg')
		expect(onNext).toHaveBeenCalledTimes(1)
		expect(onNext).toHaveBeenCalledWith(f2)
	})

	it('previous goes back and calls onPrev', async () => {
		const { emitModal, modalName, onPrev } = await setup(true)
		await emitModal('next')
		await emitModal('previous')
		expect(modalName()).toBe('f1.jpg')
		expect(onPrev).toHaveBeenCalledTimes(1)
	})

	it('close calls onClose and resets the viewer', async () => {
		const { emitModal, modalExists, onClose } = await setup(true)
		await emitModal('close')
		expect(onClose).toHaveBeenCalledTimes(1)
		// The modal is not rendered while closed, so it exposes no dialog.
		expect(modalExists()).toBe(false)
	})

	it('loops from last to first when canLoop is true', async () => {
		const { emitModal, modalName } = await setup(true)
		await emitModal('next') // f2
		await emitModal('next') // f3 (last)
		expect(modalName()).toBe('f3.jpg')
		await emitModal('next') // wraps to f1
		expect(modalName()).toBe('f1.jpg')
	})

	it('loops from first to last on previous when canLoop is true', async () => {
		const { emitModal, modalName } = await setup(true)
		await emitModal('previous')
		expect(modalName()).toBe('f3.jpg')
	})

	it('stops at the last item when canLoop is false', async () => {
		const { emitModal, modalName, modalProps } = await setup(false)
		await emitModal('next') // f2
		await emitModal('next') // f3 (last)
		expect(modalName()).toBe('f3.jpg')
		expect(modalProps().hasNext).toBe(false)
		await emitModal('next') // no-op
		expect(modalName()).toBe('f3.jpg')
	})

	it('stops at the first item on previous when canLoop is false', async () => {
		const { emitModal, modalName, modalProps } = await setup(false)
		expect(modalProps().hasPrevious).toBe(false)
		await emitModal('previous') // no-op
		expect(modalName()).toBe('f1.jpg')
	})
})

describe('Viewer goTo()', () => {
	const setup = async () => {
		const onNext = vi.fn()
		const onPrev = vi.fn()
		const ctx = mountViewer([imageHandler()])
		const f1 = makeFile({ id: 101, basename: 'f1.jpg', mime: 'image/jpeg' })
		const f2 = makeFile({ id: 102, basename: 'f2.jpg', mime: 'image/jpeg' })
		const f3 = makeFile({ id: 103, basename: 'f3.jpg', mime: 'image/jpeg' })
		await ctx.vm.open([f1, f2, f3], f1, { onNext, onPrev })
		await ctx.wrapper.vm.$nextTick()
		return { ...ctx, onNext, onPrev, f1, f2, f3 }
	}

	it('shows the requested file without firing navigation callbacks', async () => {
		const { vm, wrapper, modalName, onNext, onPrev } = await setup()
		vm.goTo(103)
		await wrapper.vm.$nextTick()
		expect(modalName()).toBe('f3.jpg')
		// History-driven move: must not push new entries via onNext/onPrev.
		expect(onNext).not.toHaveBeenCalled()
		expect(onPrev).not.toHaveBeenCalled()
	})

	it('ignores an unknown file id', async () => {
		const { vm, wrapper, modalName } = await setup()
		vm.goTo(999)
		await wrapper.vm.$nextTick()
		expect(modalName()).toBe('f1.jpg')
	})
})

describe('Viewer delete handling', () => {
	/**
	 * Get the files:node:deleted handler the viewer subscribed on mount.
	 */
	const deletedHandler = () => {
		const call = vi.mocked(subscribe).mock.calls.find((c) => c[0] === 'files:node:deleted')
		return call![1] as (node: unknown) => void
	}

	const setup = async () => {
		const ctx = mountViewer([imageHandler()])
		const f1 = makeFile({ basename: 'f1.jpg', mime: 'image/jpeg' })
		const f2 = makeFile({ basename: 'f2.jpg', mime: 'image/jpeg' })
		const f3 = makeFile({ basename: 'f3.jpg', mime: 'image/jpeg' })
		await ctx.vm.open([f1, f2, f3], f1)
		await ctx.wrapper.vm.$nextTick()
		return { ...ctx, f1, f2, f3 }
	}

	it('advances to the next file when the current one is deleted', async () => {
		const { wrapper, modalName, f1 } = await setup()
		deletedHandler()(f1)
		await wrapper.vm.$nextTick()
		expect(modalName()).toBe('f2.jpg')
	})

	it('falls back to the previous file when the last one is deleted', async () => {
		const { vm, wrapper, modalName, f3 } = await setup()
		await vm.goTo(f3.fileid!)
		await wrapper.vm.$nextTick()
		expect(modalName()).toBe('f3.jpg')

		deletedHandler()(f3)
		await wrapper.vm.$nextTick()
		expect(modalName()).toBe('f2.jpg')
	})

	it('closes the viewer when the last remaining file is deleted', async () => {
		const ctx = mountViewer([imageHandler()])
		const f1 = makeFile({ basename: 'only.jpg', mime: 'image/jpeg' })
		await ctx.vm.open([f1], f1)
		await ctx.wrapper.vm.$nextTick()

		const call = vi.mocked(subscribe).mock.calls.find((c) => c[0] === 'files:node:deleted')
		;(call![1] as (node: unknown) => void)(f1)
		await ctx.wrapper.vm.$nextTick()
		expect(ctx.modalExists()).toBe(false)
	})

	it('ignores deletion of a file not in the viewer list', async () => {
		const { wrapper, modalName } = await setup()
		deletedHandler()(makeFile({ id: 9999, basename: 'other.jpg' }))
		await wrapper.vm.$nextTick()
		expect(modalName()).toBe('f1.jpg')
	})
})

describe('Viewer action submenu', () => {
	const view = { id: 'files' } as never
	const folder = { path: '/' } as never
	const childExec = vi.fn()

	beforeAll(() => {
		// Register a parent action with a child (same shape as e.g. "Set reminder").
		registerFileAction({
			id: 'test-menu',
			displayName: () => 'Test menu',
			iconSvgInline: () => '<svg />',
			enabled: () => true,
			exec: async () => null,
		})
		registerFileAction({
			id: 'test-child',
			parent: 'test-menu',
			displayName: () => 'Child action',
			iconSvgInline: () => '<svg />',
			enabled: () => true,
			exec: childExec,
		})
	})

	it('opens the submenu and runs a child action', async () => {
		const { wrapper, vm } = mountViewer([imageHandler()])
		const f1 = makeFile({ mime: 'image/jpeg' })
		await vm.open([f1], f1, { view, folder })
		await wrapper.vm.$nextTick()

		const buttons = () => wrapper.findAll('.nc-action-button-stub')
		// Parent shown, child hidden until the submenu is opened.
		expect(buttons().find((b) => b.text().includes('Test menu'))).toBeTruthy()
		expect(wrapper.text()).not.toContain('Child action')

		await buttons().find((b) => b.text().includes('Test menu'))!.trigger('click')
		expect(wrapper.text()).toContain('Child action')

		await buttons().find((b) => b.text().includes('Child action'))!.trigger('click')
		expect(childExec).toHaveBeenCalled()
	})
})

describe('Viewer loadMore', () => {
	it('appends files returned by loadMore when reaching the last item', async () => {
		const f1 = makeFile({ basename: 'f1.jpg', mime: 'image/jpeg' })
		const f2 = makeFile({ basename: 'f2.jpg', mime: 'image/jpeg' })
		const f4 = makeFile({ basename: 'f4.jpg', mime: 'image/jpeg' })
		const loadMore = vi.fn()
			.mockResolvedValueOnce([f4])
			.mockResolvedValue([])

		const { vm, wrapper, emitModal, modalName } = mountViewer([imageHandler()])
		await vm.open([f1, f2], f1, { canLoop: false, loadMore })
		await wrapper.vm.$nextTick()

		await emitModal('next') // onto f2 (last) → triggers loadMore
		await flushPromises()
		expect(loadMore).toHaveBeenCalledTimes(1)
		expect(modalName()).toBe('f2.jpg')

		// The appended f4 is now navigable.
		await emitModal('next')
		await flushPromises()
		expect(modalName()).toBe('f4.jpg')
	})
})

describe('Viewer preload', () => {
	it('preloads both neighbours of the opened file', async () => {
		const preload = vi.fn(async () => {})
		const handler = makeHandler({
			id: 'image',
			tagname: 'oca-viewer-image',
			group: 'media',
			preload,
			enabled: (nodes) => nodes.every((n) => n.mime?.startsWith('image/')),
		})
		const { vm, wrapper } = mountViewer([handler])
		const f1 = makeFile({ basename: 'f1.jpg', mime: 'image/jpeg' })
		const f2 = makeFile({ basename: 'f2.jpg', mime: 'image/jpeg' })
		const f3 = makeFile({ basename: 'f3.jpg', mime: 'image/jpeg' })

		// Open in the middle so both neighbours exist.
		await vm.open([f1, f2, f3], f2)
		await wrapper.vm.$nextTick()

		expect(preload).toHaveBeenCalledTimes(2)
		expect(preload).toHaveBeenCalledWith(f1)
		expect(preload).toHaveBeenCalledWith(f3)
	})
})

describe('Viewer compare()', () => {
	it('enters comparison mode with navigation disabled', async () => {
		const { vm, wrapper, modalName, modalProps, modalHandlerId } = mountViewer([imageHandler()])
		const f1 = makeFile({ basename: 'left.jpg', mime: 'image/jpeg' })
		const f2 = makeFile({ basename: 'right.jpg', mime: 'image/jpeg' })

		await vm.compare(f1, f2)
		await wrapper.vm.$nextTick()

		expect(modalName()).toBe('Comparing left.jpg and right.jpg')
		expect(modalHandlerId()).toBe('image')
		// Comparison has no navigation.
		expect(modalProps().hasNext).toBe(false)
		expect(modalProps().hasPrevious).toBe(false)
	})

	it('resets comparison state when a normal file is opened afterwards', async () => {
		const { vm, wrapper, modalName } = mountViewer([imageHandler()])
		const f1 = makeFile({ basename: 'left.jpg', mime: 'image/jpeg' })
		const f2 = makeFile({ basename: 'right.jpg', mime: 'image/jpeg' })

		await vm.compare(f1, f2)
		await wrapper.vm.$nextTick()
		expect(modalName()).toContain('Comparing')

		const f3 = makeFile({ basename: 'single.jpg', mime: 'image/jpeg' })
		await vm.open([f3], f3)
		await wrapper.vm.$nextTick()

		// Comparison title gone → no comparison leak.
		expect(modalName()).toBe('single.jpg')
		expect(modalName()).not.toContain('Comparing')
	})
})

describe('Viewer sidebar', () => {
	it('emits viewer:sidebar:open with the current file source when the action is clicked', async () => {
		const { vm, wrapper } = mountViewer([imageHandler()])
		const f1 = makeFile({ basename: 'a.jpg', mime: 'image/jpeg' })
		await vm.open([f1], f1)
		await wrapper.vm.$nextTick()

		await wrapper.find('.nc-action-button-stub').trigger('click')

		expect(emit).toHaveBeenCalledWith('viewer:sidebar:open', { source: f1.source })
	})

	/**
	 * Get the handler the viewer subscribed for a files:sidebar event.
	 *
	 * @param event - The event name
	 */
	const sidebarHandler = (event: string) => {
		const call = vi.mocked(subscribe).mock.calls.find((c) => c[0] === event)
		return call![1] as () => void
	}

	it('expands the sidebar to full height only while the viewer is open', async () => {
		const { vm, wrapper } = mountViewer([imageHandler()])

		// Sidebar opened from the files list (viewer closed) must not touch the header.
		sidebarHandler('files:sidebar:opened')()
		expect(document.body.classList.contains('viewer--sidebar-fullscreen')).toBe(false)

		// With the viewer open, opening the sidebar hides the header.
		await vm.open([makeFile({ mime: 'image/jpeg' })], makeFile({ mime: 'image/jpeg' }))
		await wrapper.vm.$nextTick()
		sidebarHandler('files:sidebar:opened')()
		expect(document.body.classList.contains('viewer--sidebar-fullscreen')).toBe(true)

		// Closing the sidebar restores the header.
		sidebarHandler('files:sidebar:closed')()
		expect(document.body.classList.contains('viewer--sidebar-fullscreen')).toBe(false)
	})

	it('restores the header when the viewer closes with the sidebar open', async () => {
		const { vm, wrapper, emitModal } = mountViewer([imageHandler()])
		await vm.open([makeFile({ mime: 'image/jpeg' })], makeFile({ mime: 'image/jpeg' }))
		await wrapper.vm.$nextTick()
		sidebarHandler('files:sidebar:opened')()
		expect(document.body.classList.contains('viewer--sidebar-fullscreen')).toBe(true)

		await emitModal('close')
		expect(document.body.classList.contains('viewer--sidebar-fullscreen')).toBe(false)
	})

	it('subscribes to files:sidebar events on mount and unsubscribes on unmount', () => {
		const { wrapper } = mountViewer([imageHandler()])

		expect(subscribe).toHaveBeenCalledWith('files:sidebar:opened', expect.any(Function))
		expect(subscribe).toHaveBeenCalledWith('files:sidebar:closed', expect.any(Function))

		wrapper.unmount()

		expect(unsubscribe).toHaveBeenCalledWith('files:sidebar:opened', expect.any(Function))
		expect(unsubscribe).toHaveBeenCalledWith('files:sidebar:closed', expect.any(Function))
	})
})

describe('Viewer loading gate', () => {
	// Regression guard: the handler custom-element must stay mounted while the
	// spinner is shown (only hidden via v-show), otherwise it could never load
	// and emit `loaded`, deadlocking the viewer on the spinner forever.
	it('mounts the handler element even while still loading', async () => {
		const { vm, wrapper, renderedTags } = mountViewer([imageHandler()])
		const f1 = makeFile({ mime: 'image/jpeg' })
		await vm.open([f1], f1)
		await wrapper.vm.$nextTick()
		expect(renderedTags()).toContain('oca-viewer-image')
	})

	it('mounts both handler elements in comparison mode', async () => {
		const { vm, wrapper, renderedTags } = mountViewer([imageHandler()])
		const f1 = makeFile({ mime: 'image/jpeg' })
		const f2 = makeFile({ mime: 'image/jpeg' })
		await vm.compare(f1, f2)
		await wrapper.vm.$nextTick()
		expect(renderedTags().filter((t) => t === 'oca-viewer-image')).toHaveLength(2)
	})
})
