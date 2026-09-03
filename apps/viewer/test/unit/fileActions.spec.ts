/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { FileAction } from '@nextcloud/files'

import { beforeEach, describe, expect, it, vi } from 'vitest'

// Capture every FileAction registered by the API package so we can inspect
// their enabled()/exec() without a live Files app.
const { registered } = vi.hoisted(() => ({ registered: [] as FileAction[] }))
vi.mock('@nextcloud/files', async (orig) => {
	// eslint-disable-next-line @typescript-eslint/consistent-type-imports -- vitest importOriginal idiom
	const actual = await orig<typeof import('@nextcloud/files')>()
	return {
		...actual,
		registerFileAction: (action: FileAction) => registered.push(action),
		getFileActions: () => registered,
	}
})

// Stub the viewer so exec() can be asserted without a real Viewer instance.
vi.mock('../../src/api_package/viewer.ts')

import { Folder } from '@nextcloud/files'
import { getHandlers, registerHandler } from '../../src/api_package/index.ts'
import { getViewer } from '../../src/api_package/viewer.ts'
import { logger } from '../../src/services/logger.ts'
import { makeFile, makeHandler } from '../factories.ts'

const ACTION_VIEWER = 'viewer-open'
const ACTION_VIEWER_MENU = 'viewer-open-with'

// The shared manual mock returns a stable spied viewer.
const viewer = vi.mocked(getViewer())

/**
 * Find a captured FileAction by its id.
 *
 * @param id - The action id to look up
 */
function action(id: string): FileAction | undefined {
	return registered.find((a) => a.id === id)
}

/**
 * Build a folder node (never viewable by the handlers).
 */
function makeFolder(): Folder {
	return new Folder({
		source: 'https://cloud.example.com/remote.php/dav/files/admin/folder',
		root: '/files/admin',
		owner: 'admin',
	})
}

/**
 * Wrap nodes in the minimal action context the API package reads.
 *
 * @param nodes - Nodes for the context
 */
const VIEW = { id: 'files' } as never
const FOLDER = { path: '/folder' } as never
function ctx(nodes: unknown[]) {
	return { nodes, contents: nodes, view: VIEW, folder: FOLDER } as never
}

beforeEach(() => {
	registered.length = 0
	viewer.open.mockClear()
	// setup.ts already resets window._oca_viewer_handlers before each test.
})

describe('registerHandler validation', () => {
	it('throws on empty id', () => {
		expect(() => registerHandler(makeHandler({ id: '' })))
			.toThrow('Handler id must be a non-empty string')
	})

	it('throws on empty displayName', () => {
		expect(() => registerHandler(makeHandler({ displayName: '' })))
			.toThrow('Handler displayName must be a non-empty string')
	})

	it('throws on empty tagname', () => {
		expect(() => registerHandler(makeHandler({ tagname: '' })))
			.toThrow('Handler tagname must be a non-empty string')
	})

	it('throws on non-function enabled', () => {
		expect(() => registerHandler(makeHandler({ enabled: 'nope' as never })))
			.toThrow('Handler enabled must be a function')
	})

	it('throws on invalid theme', () => {
		expect(() => registerHandler(makeHandler({ theme: 'blue' as never })))
			.toThrow("Handler theme must be one of 'dark', 'light', 'default' if provided")
	})

	it('throws on a tagname without a hyphen', () => {
		expect(() => registerHandler(makeHandler({ tagname: 'nohyphen' })))
			.toThrow('Handler tagname must contain a hyphen (-)')
	})

	it('throws on a tagname starting with an uppercase letter', () => {
		expect(() => registerHandler(makeHandler({ tagname: 'Oca-viewer' })))
			.toThrow('Handler tagname must not start with an uppercase letter')
	})

	it('throws on a tagname with consecutive hyphens', () => {
		expect(() => registerHandler(makeHandler({ tagname: 'oca--viewer' })))
			.toThrow('Handler tagname must not contain consecutive hyphens (--)')
	})

	it('throws on a tagname starting with a hyphen', () => {
		expect(() => registerHandler(makeHandler({ tagname: '-oca-viewer' })))
			.toThrow('Handler tagname must not start or end with a hyphen (-)')
	})

	it('throws on a tagname ending with a hyphen', () => {
		expect(() => registerHandler(makeHandler({ tagname: 'oca-viewer-' })))
			.toThrow('Handler tagname must not start or end with a hyphen (-)')
	})
})

describe('registerHandler registry', () => {
	it('warns and does not double-register the same id', () => {
		const warn = vi.spyOn(logger, 'warn').mockImplementation(() => {})

		registerHandler(makeHandler({ id: 'dup' }))
		registerHandler(makeHandler({ id: 'dup' }))

		expect(warn).toHaveBeenCalledTimes(1)
		expect(warn).toHaveBeenCalledWith('Handler with id dup is already registered.')
		expect(getHandlers().size).toBe(1)

		warn.mockRestore()
	})

	it('registers the shared actions only once across handlers', () => {
		registerHandler(makeHandler({ id: 'one', tagname: 'oca-viewer-one' }))
		registerHandler(makeHandler({ id: 'two', tagname: 'oca-viewer-two' }))

		expect(registered.filter((a) => a.id === ACTION_VIEWER)).toHaveLength(1)
		expect(registered.filter((a) => a.id === ACTION_VIEWER_MENU)).toHaveLength(1)
	})
})

describe('action gate', () => {
	it('with one matching handler: default enabled, "Open with …" not enabled', () => {
		registerHandler(makeHandler({ id: 'only', tagname: 'oca-viewer-only', enabled: () => true }))
		const file = makeFile()

		expect(action(ACTION_VIEWER)!.enabled!(ctx([file]))).toBe(true)
		expect(action(ACTION_VIEWER_MENU)!.enabled!(ctx([file]))).toBe(false)
	})

	it('with two matching handlers: "Open with …" is enabled', () => {
		registerHandler(makeHandler({ id: 'a', tagname: 'oca-viewer-a', enabled: () => true }))
		registerHandler(makeHandler({ id: 'b', tagname: 'oca-viewer-b', enabled: () => true }))
		const file = makeFile()

		expect(action(ACTION_VIEWER)!.enabled!(ctx([file]))).toBe(true)
		expect(action(ACTION_VIEWER_MENU)!.enabled!(ctx([file]))).toBe(true)
	})

	it('is never enabled for folders', () => {
		registerHandler(makeHandler({ id: 'a', tagname: 'oca-viewer-a', enabled: () => true }))
		registerHandler(makeHandler({ id: 'b', tagname: 'oca-viewer-b', enabled: () => true }))
		const folder = makeFolder()

		expect(action(ACTION_VIEWER)!.enabled!(ctx([folder]))).toBe(false)
		expect(action(ACTION_VIEWER_MENU)!.enabled!(ctx([folder]))).toBe(false)
	})

	it('is not enabled when no handler matches the file', () => {
		registerHandler(makeHandler({ id: 'none', tagname: 'oca-viewer-none', enabled: () => false }))
		const file = makeFile()

		expect(action(ACTION_VIEWER)!.enabled!(ctx([file]))).toBe(false)
	})
})

describe('per-handler child action', () => {
	it('is enabled only when its own handler matches the file', () => {
		registerHandler(makeHandler({
			id: 'pdf',
			tagname: 'oca-viewer-pdf',
			enabled: (nodes) => nodes.every((n) => n.mime === 'application/pdf'),
		}))

		const child = action(`${ACTION_VIEWER_MENU}-pdf`)!
		expect(child.enabled!(ctx([makeFile({ mime: 'application/pdf' })]))).toBe(true)
		expect(child.enabled!(ctx([makeFile({ mime: 'image/png' })]))).toBe(false)
		expect(child.enabled!(ctx([makeFolder()]))).toBe(false)
	})

	it('forces its own handler id when opening', async () => {
		registerHandler(makeHandler({ id: 'pdf', tagname: 'oca-viewer-pdf', enabled: () => true }))
		const file = makeFile()

		await action(`${ACTION_VIEWER_MENU}-pdf`)!.exec(ctx([file]))

		expect(viewer.open).toHaveBeenCalledTimes(1)
		expect(viewer.open).toHaveBeenCalledWith([file], file, { view: VIEW, folder: FOLDER }, 'pdf')
	})

	it('default action opens without forcing a handler id', async () => {
		registerHandler(makeHandler({ id: 'pdf', tagname: 'oca-viewer-pdf', enabled: () => true }))
		const file = makeFile()

		await action(ACTION_VIEWER)!.exec(ctx([file]))

		expect(viewer.open).toHaveBeenCalledTimes(1)
		expect(viewer.open).toHaveBeenCalledWith([file], file, { view: VIEW, folder: FOLDER }, undefined)
	})
})
