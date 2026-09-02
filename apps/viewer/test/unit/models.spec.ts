/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { IHandler } from '../../src/api_package/index.ts'

import { describe, expect, it, vi } from 'vitest'
import { getHandlers } from '../../src/api_package/index.ts'
import { makeFile } from '../factories.ts'

// Keep FileType/FileAction/File/Folder real, but neutralise the side effects
// that registerHandler triggers on the real file-action registry.
vi.mock('@nextcloud/files', async (original) => {
	// eslint-disable-next-line @typescript-eslint/consistent-type-imports -- vitest importOriginal idiom
	const actual = await original<typeof import('@nextcloud/files')>()
	return {
		...actual,
		registerFileAction: () => {},
		getFileActions: () => [],
	}
})

// The model modules import their SFC at the top level, which drags in the whole
// @nextcloud/vue + media services tree (CSS assets, CommonJS deps). We only
// exercise the handler mime logic and the custom-element registration, so stub
// the components with a minimal Vue component object.
vi.mock('../../src/components/Videos.vue', () => ({ default: { name: 'Videos', render: () => null } }))
vi.mock('../../src/components/Audios.vue', () => ({ default: { name: 'Audios', render: () => null } }))
vi.mock('../../src/components/Images.vue', () => ({ default: { name: 'Images', render: () => null } }))

/**
 * Resolve a registered handler by id from the global viewer registry.
 *
 * @param id - Handler id to look up
 */
function handlerById(id: string): IHandler {
	const handler = getHandlers().get(id)
	if (!handler) {
		throw new Error(`Handler ${id} was not registered`)
	}
	return handler
}

describe('videos model', () => {
	it('exposes the expected metadata', async () => {
		const { registerVideoHandler } = await import('../../src/models/videos.ts')
		registerVideoHandler()
		const handler = handlerById('videos')
		expect(handler.tagname).toBe('oca-viewer-video')
		expect(handler.group).toBe('media')
	})

	it.each([
		'video/mpeg',
		'video/ogg',
		'video/webm',
		'video/mp4',
		'video/x-m4v',
		'video/x-flv',
		'video/quicktime',
	])('enables browser-supported mime %s', async (mime) => {
		const { registerVideoHandler } = await import('../../src/models/videos.ts')
		registerVideoHandler()
		const handler = handlerById('videos')
		expect(handler.enabled([makeFile({ mime })])).toBe(true)
	})

	it('enables the aliased mime video/x-matroska (maps to video/webm)', async () => {
		const { registerVideoHandler, aliasedMimes } = await import('../../src/models/videos.ts')
		registerVideoHandler()
		const handler = handlerById('videos')
		expect(aliasedMimes['video/x-matroska']).toBe('video/webm')
		expect(handler.enabled([makeFile({ mime: 'video/x-matroska' })])).toBe(true)
	})

	it('disables a non-video mime', async () => {
		const { registerVideoHandler } = await import('../../src/models/videos.ts')
		registerVideoHandler()
		const handler = handlerById('videos')
		expect(handler.enabled([makeFile({ mime: 'application/pdf' })])).toBe(false)
	})

	it('disables an empty nodes array', async () => {
		const { registerVideoHandler } = await import('../../src/models/videos.ts')
		registerVideoHandler()
		const handler = handlerById('videos')
		expect(handler.enabled([])).toBe(false)
	})

	it('requires every node to match', async () => {
		const { registerVideoHandler } = await import('../../src/models/videos.ts')
		registerVideoHandler()
		const handler = handlerById('videos')
		expect(handler.enabled([
			makeFile({ mime: 'video/mp4' }),
			makeFile({ mime: 'application/pdf' }),
		])).toBe(false)
	})
})

describe('audios model', () => {
	it('exposes the expected metadata', async () => {
		const { registerAudioHandler } = await import('../../src/models/audios.ts')
		registerAudioHandler()
		const handler = handlerById('audios')
		expect(handler.tagname).toBe('oca-viewer-audio')
		expect(handler.group).toBe('media')
	})

	it.each([
		'audio/aac',
		'audio/aacp',
		'audio/flac',
		'audio/mp4',
		'audio/mpeg',
		'audio/ogg',
		'audio/vorbis',
		'audio/wav',
		'audio/webm',
	])('enables browser-supported mime %s', async (mime) => {
		const { registerAudioHandler } = await import('../../src/models/audios.ts')
		registerAudioHandler()
		const handler = handlerById('audios')
		expect(handler.enabled([makeFile({ mime })])).toBe(true)
	})

	it('disables a non-audio mime', async () => {
		const { registerAudioHandler } = await import('../../src/models/audios.ts')
		registerAudioHandler()
		const handler = handlerById('audios')
		expect(handler.enabled([makeFile({ mime: 'video/mp4' })])).toBe(false)
	})

	it('disables an empty nodes array', async () => {
		const { registerAudioHandler } = await import('../../src/models/audios.ts')
		registerAudioHandler()
		const handler = handlerById('audios')
		expect(handler.enabled([])).toBe(false)
	})
})

describe('images model', () => {
	it('exposes the expected metadata', async () => {
		const { registerImageHandler } = await import('../../src/models/images.ts')
		registerImageHandler()
		const handler = handlerById('images')
		expect(handler.tagname).toBe('oca-viewer-image')
	})

	it.each([
		'image/apng',
		'image/bmp',
		'image/gif',
		'image/jpeg',
		'image/png',
		'image/svg+xml',
		'image/webp',
		'image/x-icon',
	])('enables always-browser-supported mime %s', async (mime) => {
		const { registerImageHandler } = await import('../../src/models/images.ts')
		registerImageHandler()
		const handler = handlerById('images')
		expect(handler.enabled([makeFile({ mime })])).toBe(true)
	})

	it('rejects a preview-only mime when no preview provider is enabled', async () => {
		// enabled_preview_providers is empty (no initial-state), so image/heic is filtered out.
		const { registerImageHandler } = await import('../../src/models/images.ts')
		registerImageHandler()
		const handler = handlerById('images')
		expect(handler.enabled([makeFile({ mime: 'image/heic' })])).toBe(false)
	})

	it('rejects a clearly non-image mime', async () => {
		const { registerImageHandler } = await import('../../src/models/images.ts')
		registerImageHandler()
		const handler = handlerById('images')
		expect(handler.enabled([makeFile({ mime: 'application/pdf' })])).toBe(false)
	})

	it('disables an empty nodes array', async () => {
		const { registerImageHandler } = await import('../../src/models/images.ts')
		registerImageHandler()
		const handler = handlerById('images')
		expect(handler.enabled([])).toBe(false)
	})
})

describe('custom elements', () => {
	// Defining the same custom element twice throws, so each of these runs once
	// and is guarded to stay resilient if the element already exists.
	it('registerVideoCustomElement defines oca-viewer-video', async () => {
		const { registerVideoCustomElement } = await import('../../src/models/videos.ts')
		try {
			registerVideoCustomElement()
		} catch {
			// already defined by a previous run
		}
		expect(window.customElements.get('oca-viewer-video')).toBeDefined()
	})

	it('registerAudioCustomElement defines oca-viewer-audio', async () => {
		const { registerAudioCustomElement } = await import('../../src/models/audios.ts')
		try {
			registerAudioCustomElement()
		} catch {
			// already defined by a previous run
		}
		expect(window.customElements.get('oca-viewer-audio')).toBeDefined()
	})

	it('registerImageCustomElement defines oca-viewer-image', async () => {
		const { registerImageCustomElement } = await import('../../src/models/images.ts')
		try {
			registerImageCustomElement()
		} catch {
			// already defined by a previous run
		}
		expect(window.customElements.get('oca-viewer-image')).toBeDefined()
	})
})
