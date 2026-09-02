/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { IFile, IFileAction, INode } from '@nextcloud/files'

import FileSvg from '@mdi/svg/svg/file.svg?raw'
import OpenInAppSvg from '@mdi/svg/svg/open-in-app.svg?raw'
import { DefaultType, FileType, getFileActions, registerFileAction } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { logger } from '../services/logger.ts'
import { openWithHistory } from '../utils/history.ts'

/** Default click-to-open action id */
const ACTION_VIEWER = 'viewer-open'
/** Parent "Open with …" selector menu id */
const ACTION_VIEWER_MENU = 'viewer-open-with'

export interface IHandler {
	/**
	 * Unique identifier for the handler
	 */
	id: string

	/**
	 * The handler translated name
	 */
	displayName: string

	/**
	 * Optional icon for the handler
	 */
	iconSvgInline?: string

	/**
	 * The custom element tag name to use for this handler.
	 */
	tagname: string

	/**
	 * Identifier to group handlers by.
	 * When opening a folder we'll check
	 * against all handlers that are enabled
	 * for the given group AND matches the
	 * group property.
	 */
	group?: string

	/**
	 * Is this enabled for the given mimes ?
	 */
	enabled: (nodes: IFile[]) => boolean

	/**
	 * Optional function to preload data for the given node.
	 * This will be called for the previous and next nodes on
	 * opening a file to allow the handler to be faster when navigating.
	 *
	 * @param node - The node to preload data for
	 * @return A promise that resolves when the data is preloaded
	 */
	preload?: (node: IFile) => Promise<void>

	/**
	 * Viewer modal theme (one of 'dark', 'light', 'default')
	 */
	theme?: 'dark' | 'light' | 'default'

	/**
	 * Whether this handler supports editing the current file in place.
	 * When true the viewer shows an "Edit" action that toggles the handler's
	 * `editing` prop (e.g. the image editor).
	 */
	canEdit?: boolean
}

/**
 * Whether at least `min` registered handlers can open the given nodes.
 * Only files are supported, folders never match.
 *
 * @param nodes - The nodes to test the handlers against
 * @param min - The minimum number of matching handlers required
 */
function countEnabledHandlers(nodes: INode[], min: number): boolean {
	if (nodes.length === 0 || nodes.some((node) => node.type !== FileType.File)) {
		return false
	}

	let count = 0
	for (const handler of getHandlers().values()) {
		if (handler.enabled(nodes as IFile[])) {
			count++
		}
		if (count >= min) {
			return true
		}
	}
	return false
}

/**
 * Default action, triggered on file click. Opens the viewer with the first
 * matching handler. Hidden from the actions menu to avoid cluttering it, but
 * it is what makes any viewable file open on a single click, regardless of how
 * many handlers are registered.
 */
const defaultViewerAction: IFileAction = {
	id: ACTION_VIEWER,
	displayName: () => t('viewer', 'View'),
	iconSvgInline: () => OpenInAppSvg,
	order: -1000,
	default: DefaultType.DEFAULT,

	enabled: ({ nodes }) => countEnabledHandlers(nodes, 1),
	async exec({ nodes, contents, view, folder }) {
		if (nodes[0]?.type !== FileType.File) {
			return null
		}

		openWithHistory(contents as IFile[], nodes[0] as IFile, view, folder)
		return null
	},
}

/**
 * Parent "Open with …" menu. Only shown when more than one handler can open
 * the given nodes, so the user is offered a real choice between them.
 */
const openWithViewerAction: IFileAction = {
	id: ACTION_VIEWER_MENU,
	displayName: () => t('viewer', 'Open with …'),
	iconSvgInline: () => OpenInAppSvg,
	order: -999,

	enabled: ({ nodes }) => countEnabledHandlers(nodes, 2),
	exec() {
		return Promise.resolve(null)
	},
}

/**
 * Register a new handler for the viewer.
 * This needs to be called before the viewer is initialized to ensure the handler is available.
 * So this should be called from an initialization script (`OCP\Util::addInitScript`).
 *
 * @param handler - The handler to register
 * @throws Error if the handler is invalid
 */
export function registerHandler(handler: IHandler): void {
	validateHandler(handler)

	window._oca_viewer_handlers ??= new Map<string, IHandler>()
	if (window._oca_viewer_handlers.has(handler.id)) {
		logger.warn(`Handler with id ${handler.id} is already registered.`)
		return
	}

	window._oca_viewer_handlers.set(handler.id, handler)

	// Selector entry shown under the "Open with …" menu. Opening forces this
	// specific handler regardless of registration order.
	registerFileAction({
		id: `${ACTION_VIEWER_MENU}-${handler.id}`,
		// TRANSLATORS: handler is the translated name of the handler.
		displayName: () => t('viewer', 'Open with {handler}', { handler: handler.displayName }),

		iconSvgInline: () => handler.iconSvgInline ?? FileSvg,
		parent: ACTION_VIEWER_MENU,
		order: -999,

		enabled: ({ nodes }) => {
			if (nodes.length === 0 || nodes.some((node) => node.type !== FileType.File)) {
				return false
			}

			return handler.enabled(nodes as IFile[])
		},
		async exec({ nodes, contents, view, folder }) {
			if (nodes[0]?.type !== FileType.File) {
				return null
			}

			openWithHistory(contents as IFile[], nodes[0] as IFile, view, folder, handler.id)
			return null
		},
	})

	// Register the shared actions only once.
	const actions = getFileActions()
	if (!actions.find((action) => action.id === ACTION_VIEWER)) {
		registerFileAction(defaultViewerAction)
		registerFileAction(openWithViewerAction)

		logger.info('Registered viewer file actions', { id: ACTION_VIEWER, menu: ACTION_VIEWER_MENU })
	}
}

/**
 * Get all registered handlers.
 */
export function getHandlers(): Map<string, IHandler> {
	return window._oca_viewer_handlers ??= new Map<string, IHandler>()
}

/**
 * Validate the handler object.
 *
 * @param handler - The handler to validate
 */
function validateHandler(handler: IHandler): void {
	const { id, displayName, group, enabled } = handler
	if (typeof id !== 'string' || id.trim() === '') {
		throw new Error('Handler id must be a non-empty string')
	}

	if (typeof displayName !== 'string' || displayName.trim() === '') {
		throw new Error('Handler displayName must be a non-empty string')
	}

	if (typeof handler.tagname !== 'string' || handler.tagname.trim() === '') {
		throw new Error('Handler tagname must be a non-empty string')
	}

	if (group && (typeof group !== 'string' || group.trim() === '')) {
		throw new Error('Handler group must be a non-empty string if provided')
	}

	if (typeof enabled !== 'function') {
		throw new Error('Handler enabled must be a function')
	}

	if (handler.preload && typeof handler.preload !== 'function') {
		throw new Error('Handler preload must be a function if provided')
	}

	if (handler.theme && !['dark', 'light', 'default'].includes(handler.theme)) {
		throw new Error("Handler theme must be one of 'dark', 'light', 'default' if provided")
	}

	validateCustomElementName(handler.tagname)
}

/**
 * Validate that the given tag name is a valid custom element name.
 *
 * @param tagname - The custom element tag name to validate
 */
function validateCustomElementName(tagname: string): void {
	if (!tagname.includes('-')) {
		throw new Error('Handler tagname must contain a hyphen (-)')
	}
	if (/^[A-Z]/.test(tagname)) {
		throw new Error('Handler tagname must not start with an uppercase letter')
	}
	if (/--/.test(tagname)) {
		throw new Error('Handler tagname must not contain consecutive hyphens (--)')
	}
	if (tagname.startsWith('-') || tagname.endsWith('-')) {
		throw new Error('Handler tagname must not start or end with a hyphen (-)')
	}
	if (!/^[a-z][a-z0-9-]*$/.test(tagname)) {
		throw new Error('Handler tagname must only contain lowercase letters, numbers, and hyphens (-)')
	}
}
