/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { VueWrapper } from '@vue/test-utils'

import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { h } from 'vue'

// The settings constants reference these l10n helpers as build-provided globals.
vi.hoisted(() => {
	globalThis.t = (app: string, text: string) => text
	globalThis.n = (app: string, singular: string, plural: string, count: number) => (count === 1 ? singular : plural)
})

const mocks = vi.hoisted(() => {
	class FilePickerClosed extends Error {}
	return {
		FilePickerClosed,
		post: vi.fn(),
		delete: vi.fn(),
		pick: vi.fn(),
		showError: vi.fn(),
		emit: vi.fn(),
		blob: null as Blob | null,
		imageScale: 1,
		image: {
			$ready: vi.fn(),
			$getTransform: vi.fn(),
			getBoundingClientRect: () => new DOMRect(10.5, 0, 200, 300),
		},
		selection: {
			width: 300,
			addEventListener: vi.fn(),
			$change: vi.fn(),
			$toCanvas: vi.fn(),
		},
		cropper: {
			destroy: vi.fn(),
			getCropperCanvas: () => ({ getBoundingClientRect: () => new DOMRect(0, 0, 300, 300) }),
			getCropperImage: () => mocks.image,
			getCropperSelection: () => mocks.selection,
		},
		Cropper: vi.fn(),
	}
})

vi.mock('@nextcloud/axios', () => ({ default: { post: mocks.post, delete: mocks.delete } }))
vi.mock('@nextcloud/auth', () => ({ getCurrentUser: () => ({ uid: 'alice' }) }))
vi.mock('@nextcloud/event-bus', () => ({ emit: mocks.emit, subscribe: vi.fn(), unsubscribe: vi.fn() }))
vi.mock('@nextcloud/initial-state', () => ({
	loadState: (app: string, key: string, fallback: unknown) => ({
		personalInfoParameters: { avatar: { name: 'avatar', scope: 'v2-local' } },
		accountParameters: { avatarChangeSupported: true },
	})[key] ?? fallback,
}))
vi.mock('@nextcloud/dialogs', () => {
	const builder = {
		setMultiSelect: () => builder,
		setMimeTypeFilter: () => builder,
		setType: () => builder,
		allowDirectories: () => builder,
		build: () => ({ pick: mocks.pick }),
	}
	return { FilePickerClosed: mocks.FilePickerClosed, getFilePickerBuilder: () => builder, showError: mocks.showError }
})
vi.mock('cropperjs', () => ({ default: mocks.Cropper }))
vi.mock('./shared/VisibilityScopeControl.vue', () => ({ default: { render: () => h('div') } }))

const flushPromises = () => new Promise((resolve) => setTimeout(resolve))

/**
 * @param generated Whether the current avatar is generated
 */
async function mountAvatarSection(generated = false) {
	window.oc_userconfig = { avatar: { version: 1, generated } } as typeof window.oc_userconfig
	const { default: AvatarSection } = await import('./AvatarSection.vue')
	return mount(AvatarSection, { global: { stubs: { NcAvatar: true } } })
}

/**
 * @param wrapper The mounted section
 * @param label Label of the button
 */
async function click(wrapper: VueWrapper, label: string) {
	await wrapper.findAll('button').find((button) => button.text() === label)!.trigger('click')
	await flushPromises()
}

/**
 * @param wrapper The mounted section
 * @param type Mime type of the uploaded file
 */
async function upload(wrapper: VueWrapper, type = 'image/png') {
	const input = wrapper.find('input[type="file"]')
	Object.defineProperty(input.element, 'files', { value: [new File(['png'], 'avatar', { type })] })
	await input.trigger('change')
}

/**
 * @param wrapper The mounted section
 */
async function startCropping(wrapper: VueWrapper) {
	await upload(wrapper)
	await vi.waitFor(() => expect(mocks.selection.addEventListener).toHaveBeenCalled())
}

/**
 * @param x Horizontal position of the selection
 * @param y Vertical position of the selection
 * @param size Width and height of the selection
 */
function changeSelection(x: number, y: number, size: number) {
	const [, listener] = mocks.selection.addEventListener.mock.lastCall! as [string, (event: Event) => void]
	const event = new CustomEvent('change', { cancelable: true, detail: { x, y, width: size, height: size } })
	listener(event)
	return event.defaultPrevented
}

/**
 * Source of the picture passed to the cropper
 */
function croppedPicture() {
	return (mocks.Cropper.mock.lastCall![0] as HTMLImageElement).src
}

/**
 * @param wrapper The mounted section
 */
function isCropperShown(wrapper: VueWrapper) {
	return !wrapper.text().includes('Upload profile picture')
}

describe('AvatarSection', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		mocks.Cropper.mockImplementation(function() {
			return mocks.cropper
		})
		mocks.image.$ready.mockResolvedValue({})
		mocks.image.$getTransform.mockImplementation(() => [mocks.imageScale, 0, 0, mocks.imageScale, 0, 0])
		mocks.selection.$toCanvas.mockResolvedValue({ toBlob: (callback: (blob: Blob | null) => void) => callback(mocks.blob) })
		mocks.blob = new Blob(['avatar'], { type: 'image/png' })
		mocks.imageScale = 1
	})

	it('rejects uploads that are no png or jpg', async () => {
		const wrapper = await mountAvatarSection()

		await upload(wrapper, 'image/gif')

		expect(mocks.showError).toHaveBeenCalledWith('Please select a valid png or jpg file')
		expect(mocks.Cropper).not.toHaveBeenCalled()
		expect(isCropperShown(wrapper)).toBe(false)
	})

	it('crops an uploaded picture', async () => {
		const wrapper = await mountAvatarSection()

		await startCropping(wrapper)

		expect(croppedPicture()).toBe('data:image/png;base64,cG5n')
		expect(mocks.selection.$change).toHaveBeenCalledWith(11, 50, 199, 199)
		expect(isCropperShown(wrapper)).toBe(true)
	})

	it('saves only once the picture is loaded', async () => {
		mocks.image.$ready.mockReturnValue(new Promise(() => {}))
		const wrapper = await mountAvatarSection()

		await upload(wrapper)
		await vi.waitFor(() => expect(mocks.image.$ready).toHaveBeenCalled())

		const save = wrapper.findAll('button').find((button) => button.text() === 'Set as profile picture')!
		expect(save.attributes('disabled')).toBeDefined()
	})

	it('keeps the selection inside the picture', async () => {
		const wrapper = await mountAvatarSection()
		await startCropping(wrapper)

		expect(changeSelection(11, 0, 199)).toBe(false)
		expect(changeSelection(11, 101, 199)).toBe(false)
		expect(changeSelection(11, 102, 199)).toBe(true)
		expect(changeSelection(10, 50, 199)).toBe(true)
		expect(changeSelection(100, 50, 199)).toBe(true)
	})

	it('reports a picture that cannot be loaded', async () => {
		mocks.image.$ready.mockRejectedValue(new Error('Failed to load the image source'))
		const wrapper = await mountAvatarSection()

		await upload(wrapper)
		await vi.waitFor(() => expect(mocks.showError).toHaveBeenCalledWith('Error cropping profile picture'))

		expect(mocks.selection.addEventListener).not.toHaveBeenCalled()
		expect(isCropperShown(wrapper)).toBe(false)
	})

	it('replaces the previous cropper when cropping another picture', async () => {
		const wrapper = await mountAvatarSection()
		await startCropping(wrapper)
		await click(wrapper, 'Cancel')

		await upload(wrapper)
		await vi.waitFor(() => expect(mocks.Cropper).toHaveBeenCalledTimes(2))

		expect(mocks.cropper.destroy).toHaveBeenCalledOnce()
	})

	it('saves the cropped picture scaled down', async () => {
		mocks.post.mockResolvedValue({ data: {} })
		mocks.imageScale = 0.25
		const wrapper = await mountAvatarSection(true)
		await startCropping(wrapper)

		await click(wrapper, 'Set as profile picture')

		expect(mocks.selection.$toCanvas).toHaveBeenCalledWith({ width: 512 })
		expect(mocks.post).toHaveBeenCalledWith(expect.any(String), expect.any(FormData))
		expect(mocks.emit).toHaveBeenCalledWith('settings:avatar:updated', expect.any(Number))
		expect(window.oc_userconfig.avatar.generated).toBe(false)
	})

	it('saves a small cropped picture in its original size', async () => {
		mocks.post.mockResolvedValue({ data: {} })
		mocks.imageScale = 1.5
		const wrapper = await mountAvatarSection()
		await startCropping(wrapper)

		await click(wrapper, 'Set as profile picture')

		expect(mocks.selection.$toCanvas).toHaveBeenCalledWith({ width: 200 })
		expect(mocks.post).toHaveBeenCalled()
	})

	it('keeps the previous picture when saving the cropped one fails', async () => {
		mocks.post.mockRejectedValue(new Error('500'))
		const wrapper = await mountAvatarSection(true)
		await startCropping(wrapper)

		await click(wrapper, 'Set as profile picture')

		expect(mocks.showError).toHaveBeenCalledWith('Error saving profile picture')
		expect(window.oc_userconfig.avatar.generated).toBe(true)
		expect(isCropperShown(wrapper)).toBe(false)
	})

	it('reports a picture that cannot be cropped', async () => {
		mocks.blob = null
		const wrapper = await mountAvatarSection()
		await startCropping(wrapper)

		await click(wrapper, 'Set as profile picture')

		expect(mocks.showError).toHaveBeenCalledWith('Error cropping profile picture')
		expect(mocks.post).not.toHaveBeenCalled()
	})

	it('reports a picture that fails to render', async () => {
		mocks.selection.$toCanvas.mockRejectedValue(new Error('Failed to load the image source'))
		const wrapper = await mountAvatarSection()
		await startCropping(wrapper)

		await click(wrapper, 'Set as profile picture')

		expect(mocks.showError).toHaveBeenCalledWith('Error cropping profile picture')
		expect(mocks.post).not.toHaveBeenCalled()
		expect(isCropperShown(wrapper)).toBe(false)
	})

	it('does nothing when the file picker is closed', async () => {
		mocks.pick.mockRejectedValue(new mocks.FilePickerClosed())
		const wrapper = await mountAvatarSection()

		await click(wrapper, 'Choose from Nextcloud Files')

		expect(mocks.post).not.toHaveBeenCalled()
		expect(mocks.showError).not.toHaveBeenCalled()
	})

	it('sets a square picture from Files', async () => {
		mocks.pick.mockResolvedValue('/photo.png')
		mocks.post.mockResolvedValue({ data: { status: 'success' } })
		const wrapper = await mountAvatarSection(true)

		await click(wrapper, 'Choose from Nextcloud Files')

		expect(mocks.post).toHaveBeenCalledWith(expect.any(String), { path: '/photo.png' })
		expect(window.oc_userconfig.avatar.generated).toBe(false)
		expect(isCropperShown(wrapper)).toBe(false)
	})

	it('crops a picture from Files that is not square', async () => {
		mocks.pick.mockResolvedValue('/photo.png')
		mocks.post.mockResolvedValue({ data: { data: 'notsquare', image: 'data:image/png;base64,' } })
		const wrapper = await mountAvatarSection()

		await click(wrapper, 'Choose from Nextcloud Files')

		expect(croppedPicture()).toBe('data:image/png;base64,')
		expect(isCropperShown(wrapper)).toBe(true)
	})

	it('shows the error of a picture from Files the server rejected', async () => {
		mocks.pick.mockResolvedValue('/photo.png')
		mocks.post.mockResolvedValue({ data: { data: { message: 'Unknown filetype' } } })
		const wrapper = await mountAvatarSection()

		await click(wrapper, 'Choose from Nextcloud Files')

		expect(mocks.showError).toHaveBeenCalledWith('Unknown filetype')
		expect(isCropperShown(wrapper)).toBe(false)
	})

	it('reports a failed request for a picture from Files', async () => {
		mocks.pick.mockResolvedValue('/photo.png')
		mocks.post.mockRejectedValue(new Error('500'))
		const wrapper = await mountAvatarSection()

		await click(wrapper, 'Choose from Nextcloud Files')

		expect(mocks.showError).toHaveBeenCalledWith('Error setting profile picture')
	})

	it('deletes the picture', async () => {
		mocks.delete.mockResolvedValue({})
		const wrapper = await mountAvatarSection()

		await click(wrapper, 'Delete picture')

		expect(mocks.delete).toHaveBeenCalled()
		expect(window.oc_userconfig.avatar.generated).toBe(true)
		expect(wrapper.text()).not.toContain('Delete picture')
	})

	it('keeps the picture when deleting it fails', async () => {
		mocks.delete.mockRejectedValue(new Error('500'))
		const wrapper = await mountAvatarSection()

		await click(wrapper, 'Delete picture')

		expect(mocks.showError).toHaveBeenCalledWith('Error removing profile picture')
		expect(window.oc_userconfig.avatar.generated).toBe(false)
		expect(wrapper.text()).toContain('Delete picture')
	})
})
