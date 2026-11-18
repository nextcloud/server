/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder, INode } from '@nextcloud/files'

import { openConflictPicker, showError } from '@nextcloud/dialogs'
import { getUploader } from '@nextcloud/files/upload'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { getClipboardImages, prepareClipboardImages, shouldIgnoreClipboardPaste, uploadClipboardImages } from './ClipboardUploadService.ts'

vi.mock('@nextcloud/dialogs', () => ({
	openConflictPicker: vi.fn(),
	showError: vi.fn(),
}))

vi.mock('@nextcloud/files/upload', () => ({
	getUploader: vi.fn(),
}))

vi.mock('@nextcloud/capabilities', () => ({
	getCapabilities: () => ({
		files: {
			forbidden_filename_characters: ['/', '\\'],
			forbidden_filenames: ['.htaccess'],
			forbidden_filename_basenames: ['con'],
			forbidden_filename_extensions: ['.exe', '.part'],
		},
	}),
}))

function createClipboardEvent(
	items: Pick<DataTransferItem, 'kind' | 'type' | 'getAsFile'>[] = [],
	files: File[] = [],
): ClipboardEvent {
	const event = new Event('paste', {
		bubbles: true,
		cancelable: true,
	})

	Object.defineProperty(event, 'clipboardData', {
		value: { items, files },
	})

	return event as ClipboardEvent
}

function createFileItem(file: File) {
	return {
		kind: 'file',
		type: file.type,
		getAsFile: () => file,
	}
}

describe('getClipboardImages', () => {
	it('returns no images for missing or empty clipboard data', () => {
		const event = new Event('paste') as ClipboardEvent

		expect(getClipboardImages(event)).toEqual([])
		expect(getClipboardImages(createClipboardEvent())).toEqual([])
	})

	it('ignores non-image clipboard content', () => {
		const event = createClipboardEvent(['text/plain', 'text/html', 'text/uri-list'].map((type) => ({
			kind: 'string',
			type,
			getAsFile: () => null,
		})))

		expect(getClipboardImages(event)).toEqual([])
	})

	it('extracts multiple images and ignores non-image files', () => {
		const png = new File(['png-content'], 'image.png', {
			type: 'image/png',
		})
		const jpeg = new File(['jpeg-content'], 'photo.jpg', {
			type: 'image/jpeg',
		})
		const document = new File(['text'], 'notes.txt', {
			type: 'text/plain',
		})

		const event = createClipboardEvent([
			createFileItem(png),
			createFileItem(document),
			createFileItem(jpeg),
		])

		const images = getClipboardImages(event)

		expect(images).toHaveLength(2)
		expect(images[0]).toBe(png)
		expect(images[1]).toBe(jpeg)
	})

	it('falls back to clipboard files when items contain no usable files', () => {
		const image = new File(['image-content'], 'photo.png', {
			type: 'image/png',
		})
		const document = new File(['text'], 'notes.txt', {
			type: 'text/plain',
		})

		const event = createClipboardEvent([
			{
				kind: 'file',
				type: 'image/png',
				getAsFile: () => null,
			},
		], [image, document])

		expect(getClipboardImages(event)).toEqual([image])
	})
})

describe('prepareClipboardImages', () => {
	it('preserves valid filenames and original file objects', () => {
		const files = [
			new File(['png-content'], 'image.png', {
				type: 'image/png',
			}),
			new File(['jpeg-content'], 'My Photo.jpg', {
				type: 'image/jpeg',
			}),
		]

		const result = prepareClipboardImages(files)

		expect(result).toHaveLength(2)
		expect(result[0]).toBe(files[0])
		expect(result[1]).toBe(files[1])
	})

	it('generates fallback filenames while preserving content and metadata', async () => {
		const files = [
			new File(['png-content'], '', {
				type: 'image/png',
				lastModified: 1234567890,
			}),
			new File(['jpeg-content'], 'con.jpg', {
				type: 'image/jpeg',
				lastModified: 9876543210,
			}),
		]

		const result = prepareClipboardImages(files)

		expect(result.map((file) => file.name)).toEqual([
			'Clipboard.png',
			'Clipboard.jpg',
		])

		for (const [index, file] of result.entries()) {
			expect(file.type).toBe(files[index].type)
			expect(file.size).toBe(files[index].size)
			expect(file.lastModified).toBe(files[index].lastModified)
			expect(await file.arrayBuffer()).toEqual(await files[index].arrayBuffer())
		}
	})

	it('generates unique filenames without colliding with other incoming files', async () => {
		const files = [
			new File(['first'], 'image.png', {
				type: 'image/png',
			}),
			new File(['second'], 'image.png', {
				type: 'image/png',
				lastModified: 1234567890,
			}),
			new File(['third'], 'image (1).png', {
				type: 'image/png',
			}),
		]

		const result = prepareClipboardImages(files)

		expect(result.map((file) => file.name)).toEqual([
			'image.png',
			'image (2).png',
			'image (1).png',
		])

		expect(new Set(result.map((file) => file.name)).size).toBe(3)
		expect(result[0]).toBe(files[0])
		expect(result[2]).toBe(files[2])

		expect(result[1]).not.toBe(files[1])
		expect(result[1].type).toBe(files[1].type)
		expect(result[1].size).toBe(files[1].size)
		expect(result[1].lastModified).toBe(files[1].lastModified)
		expect(await result[1].arrayBuffer()).toEqual(await files[1].arrayBuffer())
	})
})

describe('uploadClipboardImages', () => {
	const batchUpload = vi.fn()

	function createContext() {
		return {
			folder: {
				source: '/remote.php/dav/files/testuser/photos',
			} as IFolder,
			getContents: vi.fn().mockResolvedValue([]),
		}
	}

	function createImageEvent() {
		const image = new File(['image-content'], 'photo.png', {
			type: 'image/png',
		})

		return createClipboardEvent([createFileItem(image)])
	}

	beforeEach(() => {
		vi.mocked(getUploader, { partial: true }).mockReturnValue({
			batchUpload,
		})
	})

	afterEach(() => {
		vi.resetAllMocks()
		document.body.replaceChildren()
	})

	it('ignores prevented events, editable targets and visible dialogs', async () => {
		const context = createContext()

		// An already prevented event
		const preventedEvent = createImageEvent()
		preventedEvent.preventDefault()

		expect(shouldIgnoreClipboardPaste(preventedEvent)).toBe(true)
		await uploadClipboardImages(preventedEvent, context)

		// An editable element has focus
		const input = document.createElement('input')
		document.body.appendChild(input)
		input.focus()

		const editableEvent = createImageEvent()

		expect(shouldIgnoreClipboardPaste(editableEvent)).toBe(true)
		await uploadClipboardImages(editableEvent, context)

		expect(editableEvent.defaultPrevented).toBe(false)

		input.remove()

		// A visible modal dialog
		const modal = document.createElement('div')
		modal.classList.add('modal-mask')

		const checkVisibility = vi.fn().mockReturnValue(true)

		Object.defineProperty(modal, 'checkVisibility', {
			value: checkVisibility,
		})

		document.body.appendChild(modal)

		const modalEvent = createImageEvent()

		expect(shouldIgnoreClipboardPaste(modalEvent)).toBe(true)
		await uploadClipboardImages(modalEvent, context)

		expect(modalEvent.defaultPrevented).toBe(false)
		expect(getUploader).not.toHaveBeenCalled()
		expect(batchUpload).not.toHaveBeenCalled()
		expect(context.getContents).not.toHaveBeenCalled()
		expect(showError).not.toHaveBeenCalled()

		// A hidden modal must not prevent clipboard uploads
		checkVisibility.mockReturnValue(false)
		batchUpload.mockResolvedValue(undefined)

		const hiddenModalEvent = createImageEvent()

		expect(shouldIgnoreClipboardPaste(hiddenModalEvent)).toBe(false)
		await uploadClipboardImages(hiddenModalEvent, context)

		expect(hiddenModalEvent.defaultPrevented).toBe(true)
		expect(batchUpload).toHaveBeenCalledTimes(1)
		expect(showError).not.toHaveBeenCalled()
	})

	it('uploads images to the correct destination and prevents native paste', async () => {
		const context = createContext()

		const image = new File(['image-content'], 'photo.png', {
			type: 'image/png',
		})

		const event = createClipboardEvent([createFileItem(image)])

		let mapping: Record<string, string> | false | undefined

		batchUpload.mockImplementation(async (_path, _files, options) => {
			mapping = await options.callback(['photo.png'], '')
		})

		await uploadClipboardImages(event, context)

		expect(event.defaultPrevented).toBe(true)
		expect(batchUpload).toHaveBeenCalledTimes(1)

		expect(batchUpload).toHaveBeenCalledWith(
			'',
			[image],
			expect.objectContaining({
				root: context.folder.source,
				callback: expect.any(Function),
			}),
		)

		expect(context.getContents).toHaveBeenCalledWith('')

		expect(mapping).toEqual({
			'photo.png': 'photo.png',
		})

		expect(openConflictPicker).not.toHaveBeenCalled()
		expect(showError).not.toHaveBeenCalled()
	})

	it('resolves mixed conflicts and preserves non-conflicting files', async () => {
		const context = createContext()

		const files = [
			new File(['replacement'], 'replace.png', { type: 'image/png' }),
			new File(['duplicate'], 'duplicate.png', { type: 'image/png' }),
			new File(['skipped'], 'skip.png', { type: 'image/png' }),
			new File(['unique'], 'unique.png', { type: 'image/png' }),
		]

		const existingNodes = [
			{ basename: 'replace.png' } as INode,
			{ basename: 'duplicate.png' } as INode,
			{ basename: 'skip.png' } as INode,
			{ basename: 'duplicate (1).png' } as INode,
			{ basename: 'unrelated.txt' } as INode,
		]

		context.getContents.mockResolvedValue(existingNodes)

		vi.mocked(openConflictPicker).mockResolvedValue({
			selected: [files[0]],
			renamed: [files[1]],
			skipped: [files[2]],
		})

		let mapping: Record<string, string> | false | undefined

		batchUpload.mockImplementation(async (_path, _files, options) => {
			mapping = await options.callback(
				files.map((file) => file.name),
				'',
			)
		})

		await uploadClipboardImages(
			createClipboardEvent(files.map(createFileItem)),
			context,
		)

		expect(openConflictPicker).toHaveBeenCalledTimes(1)

		expect(openConflictPicker).toHaveBeenCalledWith(
			'',
			files.slice(0, 3),
			existingNodes.slice(0, 3),
			{ recursive: false },
		)

		expect(mapping).toEqual({
			'replace.png': 'replace.png',
			'duplicate.png': 'duplicate (2).png',
			'unique.png': 'unique.png',
		})

		expect(showError).not.toHaveBeenCalled()
	})

	it('cancels the entire batch when conflict resolution is dismissed', async () => {
		const context = createContext()

		const files = [
			new File(['duplicate'], 'photo.png', { type: 'image/png' }),
			new File(['unique'], 'unique.png', { type: 'image/png' }),
		]

		context.getContents.mockResolvedValue([
			{ basename: 'photo.png' } as INode,
		])

		vi.mocked(openConflictPicker).mockResolvedValue(null)

		let mapping: Record<string, string> | false | undefined

		batchUpload.mockImplementation(async (_path, _files, options) => {
			mapping = await options.callback(
				files.map((file) => file.name),
				'',
			)
		})

		await uploadClipboardImages(
			createClipboardEvent(files.map(createFileItem)),
			context,
		)

		expect(openConflictPicker).toHaveBeenCalledTimes(1)
		expect(mapping).toBe(false)
		expect(showError).not.toHaveBeenCalled()
	})

	it('processes batches sequentially and recovers after an upload failure', async () => {
		const context = createContext()

		let rejectUpload!: (error: Error) => void

		const firstUpload = new Promise<void>((_resolve, reject) => {
			rejectUpload = reject
		})

		batchUpload
			.mockImplementationOnce(() => firstUpload)
			.mockResolvedValueOnce(undefined)

		const first = uploadClipboardImages(createImageEvent(), context)
		const second = uploadClipboardImages(createImageEvent(), context)

		await Promise.resolve()

		try {
			expect(batchUpload).toHaveBeenCalledTimes(1)
		} finally {
			rejectUpload(new Error('Upload failed'))
			await Promise.all([first, second])
		}

		expect(batchUpload).toHaveBeenCalledTimes(2)
		expect(showError).toHaveBeenCalledTimes(1)
	})

	it('aborts conflict resolution when destination fetching fails', async () => {
		const context = createContext()

		context.getContents.mockRejectedValue(new Error('Clipboard upload destination changed'))

		let mapping: Record<string, string> | false | undefined

		batchUpload.mockImplementation(async (_path, _files, options) => {
			mapping = await options.callback(['photo.png'], '')
		})

		await uploadClipboardImages(createImageEvent(), context)

		expect(context.getContents).toHaveBeenCalledWith('')
		expect(mapping).toBe(false)
		expect(openConflictPicker).not.toHaveBeenCalled()
		expect(showError).toHaveBeenCalledTimes(1)
	})
})
