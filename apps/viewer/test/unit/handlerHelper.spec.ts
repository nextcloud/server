/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { describe, expect, it } from 'vitest'
import { getHandler, getHandlerForFile } from '../../src/helpers/handlerHelper.ts'
import { makeFile, makeHandler, registerTestHandlers } from '../factories.ts'

describe('getHandlerForFile', () => {
	it('returns the first handler whose enabled matches', () => {
		registerTestHandlers(makeHandler({ id: 'image', tagname: 'oca-viewer-image', enabled: (nodes) => nodes.every((n) => n.mime?.startsWith('image/')) }))
		expect(getHandlerForFile(makeFile({ mime: 'image/png' }))?.id).toBe('image')
	})

	it('returns undefined when no handler matches', () => {
		registerTestHandlers(makeHandler({ id: 'image', enabled: (nodes) => nodes.every((n) => n.mime?.startsWith('image/')) }))
		expect(getHandlerForFile(makeFile({ mime: 'application/zip' }))).toBeUndefined()
	})

	it('respects the group filter', () => {
		registerTestHandlers(makeHandler({ id: 'video', tagname: 'oca-viewer-video', group: 'media', enabled: () => true }))
		expect(getHandlerForFile(makeFile(), 'media')?.id).toBe('video')
		expect(getHandlerForFile(makeFile(), 'other')).toBeUndefined()
	})
})

describe('getHandler', () => {
	it('returns a registered handler by id', () => {
		registerTestHandlers(makeHandler({ id: 'abc' }))
		expect(getHandler('abc')?.id).toBe('abc')
		expect(getHandler('missing')).toBeUndefined()
	})
})
