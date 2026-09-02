/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { DefaultType } from '@nextcloud/files'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { makeFile } from '../factories.ts'

const fileActionsList = vi.hoisted(() => [] as unknown[])
vi.mock('@nextcloud/files', async (orig) => ({
	// eslint-disable-next-line @typescript-eslint/consistent-type-imports -- vitest importOriginal idiom
	...(await orig<typeof import('@nextcloud/files')>()),
	getFileActions: () => fileActionsList,
}))

const { useViewerActions } = await import('../../src/composables/useViewerActions.ts')

const view = { id: 'files' } as never
const folder = { path: '/folder' } as never

/**
 * Build a stub file action.
 *
 * @param overrides - fields to override on the action
 */
function makeAction(overrides: Record<string, unknown> = {}) {
	return {
		id: 'action',
		displayName: () => 'Action',
		iconSvgInline: () => '<svg />',
		enabled: () => true,
		exec: vi.fn(),
		order: 0,
		parent: undefined,
		...overrides,
	}
}

describe('useViewerActions', () => {
	beforeEach(() => {
		fileActionsList.length = 0
	})

	it('is empty when no view/folder context is available', () => {
		fileActionsList.push(makeAction({ id: 'download' }))
		const { actions } = useViewerActions(() => makeFile(), () => [], () => undefined, () => undefined)
		expect(actions.value).toEqual([])
	})

	it('returns enabled actions sorted by order, excluding viewer-open*, details and children', () => {
		fileActionsList.push(
			makeAction({ id: 'viewer-open' }),
			makeAction({ id: 'viewer-open-with-images' }),
			makeAction({ id: 'details' }),
			makeAction({ id: 'child', parent: 'menu' }),
			makeAction({ id: 'disabled', enabled: () => false }),
			makeAction({ id: 'delete', order: 2 }),
			makeAction({ id: 'download', order: 1 }),
		)
		const file = makeFile()
		const { actions } = useViewerActions(() => file, () => [file], () => view, () => folder)
		expect(actions.value.map((a) => a.id)).toEqual(['download', 'delete'])
	})

	it('excludes actions with a custom inline renderer and hidden actions', () => {
		fileActionsList.push(
			makeAction({ id: 'sharing-status', renderInline: () => document.createElement('span') }),
			makeAction({ id: 'hidden', default: DefaultType.HIDDEN }),
			makeAction({ id: 'download' }),
		)
		const file = makeFile()
		const { actions } = useViewerActions(() => file, () => [file], () => view, () => folder)
		expect(actions.value.map((a) => a.id)).toEqual(['download'])
	})

	it('excludes actions whose label is empty in the viewer context', () => {
		fileActionsList.push(
			makeAction({ id: 'sharing-status', displayName: () => '' }),
			makeAction({ id: 'download' }),
		)
		const file = makeFile()
		const { actions } = useViewerActions(() => file, () => [file], () => view, () => folder)
		expect(actions.value.map((a) => a.id)).toEqual(['download'])
	})

	it('splits parent actions from their children and groups submenus', () => {
		fileActionsList.push(
			makeAction({ id: 'reminder-menu', displayName: () => 'Set reminder' }),
			makeAction({ id: 'reminder-today', parent: 'reminder-menu', displayName: () => 'Today' }),
			makeAction({ id: 'reminder-custom', parent: 'reminder-menu', displayName: () => 'Custom' }),
			makeAction({ id: 'download' }),
		)
		const file = makeFile()
		const { actions, enabledSubmenuActions, isValidMenu } = useViewerActions(() => file, () => [file], () => view, () => folder)

		// Children are not shown at the top level.
		expect(actions.value.map((a) => a.id)).toEqual(['reminder-menu', 'download'])
		// They are grouped under their parent.
		expect(enabledSubmenuActions.value['reminder-menu']!.map((a) => a.id)).toEqual(['reminder-today', 'reminder-custom'])
		expect(isValidMenu(actions.value[0]!)).toBe(true)
		expect(isValidMenu(actions.value[1]!)).toBe(false)
	})

	it('executes an action with the current file context', async () => {
		const exec = vi.fn()
		fileActionsList.push(makeAction({ id: 'download', exec }))
		const file = makeFile()
		const { actions, execAction } = useViewerActions(() => file, () => [file], () => view, () => folder)
		await execAction(actions.value[0]!)
		expect(exec).toHaveBeenCalledWith(expect.objectContaining({
			nodes: [file],
			view,
			folder,
			contents: [file],
		}))
	})
})
