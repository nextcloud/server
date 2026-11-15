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
		cropper: {
			replace: vi.fn(),
			destroy: vi.fn(),
			getCroppedCanvas: vi.fn(() => ({ width: 1024 })),
			scale: vi.fn(),
		},
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
vi.mock('cropperjs', () => ({
	default: vi.fn(function() {
		return mocks.cropper
	}),
}))
vi.mock('cropperjs/dist/cropper.css', () => ({}))
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
 */
function isCropperShown(wrapper: VueWrapper) {
	return !wrapper.text().includes('Upload profile picture')
}

describe('AvatarSection', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		mocks.cropper.scale.mockReturnValue({ getCroppedCanvas: () => ({ toBlob: (callback: (blob: Blob | null) => void) => callback(mocks.blob) }) })
		mocks.blob = new Blob(['avatar'], { type: 'image/png' })
	})

	it('rejects uploads that are no png or jpg', async () => {
		const wrapper = await mountAvatarSection()
		const input = wrapper.find('input[type="file"]')
		Object.defineProperty(input.element, 'files', { value: [new File(['gif'], 'avatar.gif', { type: 'image/gif' })] })

		await input.trigger('change')

		expect(mocks.showError).toHaveBeenCalledWith('Please select a valid png or jpg file')
		expect(mocks.cropper.replace).not.toHaveBeenCalled()
		expect(isCropperShown(wrapper)).toBe(false)
	})

	it('crops an uploaded picture', async () => {
		const wrapper = await mountAvatarSection()
		const input = wrapper.find('input[type="file"]')
		Object.defineProperty(input.element, 'files', { value: [new File(['png'], 'avatar.png', { type: 'image/png' })] })

		await input.trigger('change')
		await vi.waitFor(() => expect(mocks.cropper.replace).toHaveBeenCalled())

		expect(isCropperShown(wrapper)).toBe(true)
	})

	it('saves the cropped picture scaled down', async () => {
		mocks.post.mockResolvedValue({ data: {} })
		const wrapper = await mountAvatarSection(true)

		await click(wrapper, 'Set as profile picture')

		expect(mocks.cropper.scale).toHaveBeenCalledWith(0.5, 0.5)
		expect(mocks.post).toHaveBeenCalledWith(expect.any(String), expect.any(FormData))
		expect(mocks.emit).toHaveBeenCalledWith('settings:avatar:updated', expect.any(Number))
		expect(window.oc_userconfig.avatar.generated).toBe(false)
	})

	it('keeps the previous picture when saving the cropped one fails', async () => {
		mocks.post.mockRejectedValue(new Error('500'))
		const wrapper = await mountAvatarSection(true)

		await click(wrapper, 'Set as profile picture')

		expect(mocks.showError).toHaveBeenCalledWith('Error saving profile picture')
		expect(window.oc_userconfig.avatar.generated).toBe(true)
		expect(isCropperShown(wrapper)).toBe(false)
	})

	it('reports a picture that cannot be cropped', async () => {
		mocks.blob = null
		const wrapper = await mountAvatarSection()

		await click(wrapper, 'Set as profile picture')

		expect(mocks.showError).toHaveBeenCalledWith('Error cropping profile picture')
		expect(mocks.post).not.toHaveBeenCalled()
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

		expect(mocks.cropper.replace).toHaveBeenCalledWith('data:image/png;base64,')
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
