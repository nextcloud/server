/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFile, NodeData } from '@nextcloud/files'
import type { FileStat, ResponseDataDetailed } from 'webdav'

import { getCurrentUser } from '@nextcloud/auth'
import { File, Permission } from '@nextcloud/files'
import { getClient, getDefaultPropfind, getRemoteURL, getRootPath, resultToNode } from '@nextcloud/files/dav'
import { getLoggerBuilder } from '@nextcloud/logger'
import { canView, getViewer } from '@nextcloud/viewer'

/**
 * The release this shim goes away in.
 *
 * Named in every warning so that an app author reading a log knows how long
 * they have rather than only that something is deprecated.
 */
const REMOVED_IN = 40

const logger = getLoggerBuilder()
	.setApp('viewer')
	.detectUser()
	.build()

/**
 * The shape apps passed around before the viewer took nodes.
 *
 * Loose on purpose: this came from callers rather than from us, and most of
 * them filled in only the few fields they had.
 */
interface LegacyFileInfo {
	fileid?: number | string
	filename?: string
	basename?: string
	source?: string
	mime?: string
	etag?: string
	hasPreview?: boolean
	size?: number
	mtime?: number
	permissions?: number
}

interface LegacyOpenOptions {
	path?: string
	fileInfo?: LegacyFileInfo
	list?: LegacyFileInfo[]
	enableSidebar?: boolean
	loadMore?: () => LegacyFileInfo[] | Promise<LegacyFileInfo[]>
	canLoop?: boolean
	startSlideshow?: boolean
	onPrev?: (file: IFile) => void
	onNext?: (file: IFile) => void
	onClose?: () => void
}

/** What the getters answer with, kept from the last call to open */
interface LegacyState {
	file: string
	files: LegacyFileInfo[]
	enableSidebar: boolean
	canLoop: boolean
	startSlideshow: boolean
	loadMore: () => LegacyFileInfo[] | Promise<LegacyFileInfo[]>
	onPrev: (file: IFile) => void
	onNext: (file: IFile) => void
	onClose: () => void
	overrideHandlerId: string | null
}

/** The state of a viewer with nothing open */
function emptyState(): LegacyState {
	return {
		file: '',
		files: [],
		enableSidebar: true,
		canLoop: true,
		startSlideshow: false,
		loadMore: () => [],
		onPrev: () => {},
		onNext: () => {},
		onClose: () => {},
		overrideHandlerId: null,
	}
}

let state: LegacyState = emptyState()

/**
 * Say that something reached for the old API, and what to reach for instead.
 *
 * Warned on every access rather than once per caller: these end up in the
 * logs an administrator sends with a bug report, and one line at the top of
 * a session is the one that scrolls away.
 *
 * @param member - the property or method that was used
 * @param replacement - what to call instead
 */
function deprecated(member: string, replacement: string): void {
	logger.warn(`OCA.Viewer.${member} is deprecated and will be removed in Nextcloud ${REMOVED_IN}. Use ${replacement} from the @nextcloud/viewer package instead.`)
}

/**
 * Look a path up on the dav endpoint so the viewer can be handed a node.
 *
 * @param path - an absolute path in the user's files
 */
async function nodeFromPath(path: string): Promise<IFile> {
	const result = await getClient().stat(getRootPath() + path, {
		details: true,
		data: getDefaultPropfind(),
	}) as ResponseDataDetailed<FileStat>
	return resultToNode(result.data) as IFile
}

/**
 * The last part of a path, for a caller that did not say what the file is
 * called.
 *
 * @param source - the address the file is served from
 */
function basenameOf(source: string): string {
	const name = source.split('/').pop() ?? ''
	try {
		return decodeURIComponent(name)
	} catch {
		return name
	}
}

/**
 * The folder part of an address, as a path.
 *
 * @param source - the address the file is served from
 */
function directoryOf(source: string): string {
	try {
		const { pathname } = new URL(source, window.location.origin)
		return pathname.slice(0, pathname.lastIndexOf('/')) || '/'
	} catch {
		return '/'
	}
}

/**
 * Build a node out of what an app handed over.
 *
 * Callers filled in what they had, so most of this is defaulting. A file
 * with a `source` of its own is served from somewhere that is not dav, and
 * keeps it; everything else is addressed under the user's files.
 *
 * @param info - the file info an app passed to open
 */
function nodeFromFileInfo(info: LegacyFileInfo): IFile {
	const owner = getCurrentUser()?.uid ?? null
	// A node is addressed by URL. What apps passed here was a path inside the
	// user's files, so it needs the dav endpoint in front of it.
	const davSource = info.filename
		? getRemoteURL() + getRootPath() + info.filename
		: undefined
	const source = info.source ?? davSource

	if (source === undefined) {
		throw new Error('Viewer file info needs either a source or a filename')
	}

	const data: NodeData = {
		source,
		// A file under the user's files is relative to their dav home. One
		// served from somewhere else has no home, so the folder it sits in
		// stands in for it and the node still knows its own name.
		root: info.source === undefined ? getRootPath() : directoryOf(info.source),
		displayname: info.basename ?? basenameOf(source),
		mime: info.mime ?? 'application/octet-stream',
		owner,
		permissions: info.permissions ?? Permission.READ,
		attributes: {
			etag: info.etag,
			hasPreview: info.hasPreview,
		},
	}

	// Set apart rather than defaulted: a node is not allowed to carry these
	// as undefined, and a caller that did not know them leaves them out
	if (info.fileid !== undefined) {
		data.id = Number(info.fileid)
	}
	if (info.size !== undefined) {
		data.size = info.size
	}
	if (info.mtime !== undefined) {
		data.mtime = new Date(info.mtime)
	}

	return new File(data)
}

/**
 * Whether any handler would take a file of this type.
 *
 * The answer is worked out by asking the handlers about a file that has
 * nothing but the type, because a handler is asked about nodes rather than
 * about types. One that looks at more than the type, at whether a preview
 * exists say, can answer differently here than it would for a real file.
 *
 * @param mime - the type to ask about
 */
function canOpenMime(mime: string): boolean {
	try {
		return canView(new File({
			source: `${getRemoteURL()}${getRootPath()}/.viewer-mime-probe`,
			root: getRootPath(),
			mime,
			owner: getCurrentUser()?.uid ?? null,
			permissions: Permission.READ,
			attributes: { hasPreview: true },
		}))
	} catch (error) {
		logger.debug('Could not work out whether the viewer handles this type', { mime, error })
		return false
	}
}

/**
 * A stand-in for the list of types the viewer used to publish.
 *
 * Handlers no longer carry a list of types, they answer about a file, so
 * there is nothing left to enumerate. Every caller we know of asks whether
 * one type is in the list, so that is what this answers; anything that reads
 * it as a list sees an empty one rather than a wrong one.
 */
const mimetypes = new Proxy([] as string[], {
	get(target, property, receiver) {
		if (property === 'includes') {
			return (mime: string) => canOpenMime(mime)
		}
		if (property === 'indexOf') {
			return (mime: string) => (canOpenMime(mime) ? 0 : -1)
		}
		return Reflect.get(target, property, receiver)
	},
})

/**
 * Open a file, the way the viewer app used to be asked to.
 *
 * @param options - what to open, and how
 */
async function open(options: LegacyOpenOptions = {}): Promise<void> {
	deprecated('open()', 'getViewer().open()')

	const {
		path,
		fileInfo,
		list = [],
		enableSidebar = true,
		loadMore = () => [],
		canLoop = true,
		startSlideshow = false,
		onPrev = () => {},
		onNext = () => {},
		onClose = () => {},
	} = options

	// Kept from the old implementation: these were thrown rather than logged,
	// and an app relying on the throw should keep getting it
	if (!path && !fileInfo) {
		throw new Error('Viewer needs either an URL or path to open. None given')
	}
	if (path && !path.startsWith('/')) {
		throw new Error('Please use an absolute path')
	}
	if (!Array.isArray(list)) {
		throw new Error('The files list must be an array')
	}
	if (typeof loadMore !== 'function') {
		throw new Error('The loadMore method must be a function')
	}

	const handlerId = state.overrideHandlerId ?? undefined
	state = {
		...emptyState(),
		file: path ?? fileInfo?.filename ?? '',
		files: list,
		enableSidebar,
		canLoop,
		startSlideshow,
		loadMore,
		onPrev,
		onNext,
		onClose,
	}

	let target = path ? await nodeFromPath(path) : nodeFromFileInfo(fileInfo!)
	const nodes = list.map(nodeFromFileInfo)

	// The file to show has to be one of the nodes handed over, and the list
	// the caller gave usually already holds it under a different object
	const known = nodes.find((node) => (
		(target.fileid !== undefined && node.fileid === target.fileid)
		|| node.source === target.source
	))
	if (known) {
		target = known
	} else {
		nodes.push(target)
	}

	await getViewer().open(nodes, target, {
		enableSidebar,
		canLoop,
		startSlideshow,
		onPrev,
		onNext,
		// The viewer being closed from the inside has to clear what the
		// getters answer with, or the file stays open as far as they know
		onClose: () => {
			state = emptyState()
			onClose()
		},
		loadMore: async () => (await loadMore()).map(nodeFromFileInfo),
	}, handlerId)
}

/**
 * What the viewer app used to put on the page.
 *
 * @deprecated since 36, removed in 40. Use the `@nextcloud/viewer` package.
 */
export interface LegacyViewerApi {
	open(options?: LegacyOpenOptions): Promise<void>
	openWith(handlerId: string, options?: LegacyOpenOptions): Promise<void>
	compare(fileInfo: LegacyFileInfo, compareFileInfo: LegacyFileInfo): Promise<void>
	close(): void
	readonly file: string
	readonly files: LegacyFileInfo[]
	readonly list: LegacyFileInfo[]
	readonly mimetypes: string[]
	readonly enableSidebar: boolean
	readonly canLoop: boolean
	readonly startSlideshow: boolean
	readonly loadMore: () => LegacyFileInfo[] | Promise<LegacyFileInfo[]>
	readonly onPrev: (file: IFile) => void
	readonly onNext: (file: IFile) => void
	readonly onClose: () => void
	readonly overrideHandlerId: string | null
}

/**
 * Install the old global on the page.
 *
 * Nothing in the server calls it any more. It is here for apps that have not
 * moved over yet, and goes away in Nextcloud {@link REMOVED_IN}.
 */
export function installLegacyViewerApi(): void {
	window.OCA ??= {} as typeof window.OCA

	const api: LegacyViewerApi = {
		open,

		openWith(handlerId: string, options: LegacyOpenOptions = {}): Promise<void> {
			deprecated('openWith()', 'the handler argument of getViewer().open()')
			state.overrideHandlerId = handlerId
			return open(options)
		},

		compare(fileInfo: LegacyFileInfo, compareFileInfo: LegacyFileInfo): Promise<void> {
			deprecated('compare()', 'getViewer().compare()')
			return getViewer().compare(nodeFromFileInfo(fileInfo), nodeFromFileInfo(compareFileInfo))
		},

		close(): void {
			deprecated('close()', 'getViewer().close()')
			state = emptyState()
			getViewer().close()
		},

		get file(): string {
			deprecated('file', 'the node you passed to getViewer().open()')
			return state.file
		},

		get files(): LegacyFileInfo[] {
			deprecated('files', 'the nodes you passed to getViewer().open()')
			return state.files
		},

		// Never a getter on the viewer app, so this always read as undefined.
		// Kept as the list it was meant to be rather than as the mistake.
		get list(): LegacyFileInfo[] {
			deprecated('list', 'the nodes you passed to getViewer().open()')
			return state.files
		},

		get mimetypes(): string[] {
			deprecated('mimetypes', 'canView()')
			return mimetypes
		},

		get enableSidebar(): boolean {
			deprecated('enableSidebar', 'the options you passed to getViewer().open()')
			return state.enableSidebar
		},

		get canLoop(): boolean {
			deprecated('canLoop', 'the options you passed to getViewer().open()')
			return state.canLoop
		},

		get startSlideshow(): boolean {
			deprecated('startSlideshow', 'the options you passed to getViewer().open()')
			return state.startSlideshow
		},

		get loadMore() {
			deprecated('loadMore', 'the options you passed to getViewer().open()')
			return state.loadMore
		},

		get onPrev() {
			deprecated('onPrev', 'the options you passed to getViewer().open()')
			return state.onPrev
		},

		get onNext() {
			deprecated('onNext', 'the options you passed to getViewer().open()')
			return state.onNext
		},

		get onClose() {
			deprecated('onClose', 'the options you passed to getViewer().open()')
			return state.onClose
		},

		get overrideHandlerId(): string | null {
			deprecated('overrideHandlerId', 'the handler argument of getViewer().open()')
			return state.overrideHandlerId
		},
	}

	window.OCA.Viewer = api
}
