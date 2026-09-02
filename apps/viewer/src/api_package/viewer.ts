/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFile, IFolder, IView } from '@nextcloud/files'
import type ViewerVue from '../views/Viewer.vue'

/**
 * List of props provided to your custom component.
 * Use it like this:
 * ```ts
 * <script setup lang="ts">
 * import type { ViewerProps } from '@nextcloud/viewer'
 *
 * const props = defineProps<ViewerProps>()
 * ```
 */
export interface ViewerProps {
	/**
	 * The file to be displayed
	 */
	file: IFile

	/**
	 * The list of files currently opened in the viewer
	 */
	files: IFile[]

	/**
	 * The max height of the viewer container
	 */
	maxHeight: number

	/**
	 * The max width of the viewer container
	 */
	maxWidth: number

	/**
	 * Whether the viewer is in editing mode
	 */
	editing: boolean

	/**
	 * Whether the sidebar is shown
	 */
	isSidebarShown: boolean

	/**
	 * Optional client-side source to display instead of fetching from the server
	 * (e.g. an object URL for a freshly edited image not yet reflected in the
	 * server preview). Handlers that support it should prefer this over `file`.
	 */
	localSource?: string
}

/**
 * List of emits that can be emitted by your custom component.
 * Use it like this:
 * ```ts
 * <script setup lang="ts">
 * import type { ViewerEmits } from '@nextcloud/viewer'
 *
 * const emit = defineEmits<ViewerEmits>()
 * ```
 */
export interface ViewerEmits {
	/**
	 * Emit this event to notify the viewer that your component is done loading.
	 */
	loaded: []

	/**
	 * Emit this event to notify the viewer that an  error occurred while loading the file.
	 * If provided, a custom error message will be shown.
	 *
	 * @param error The error that occurred
	 */
	errored: [Error]

	/**
	 * Emit this event to disable or enable the swiping gesture.
	 * This is usually used when your component provides its own swiping mechanism (e.g. the video player controls).
	 */
	'update:canSwipe': [boolean]

	/**
	 * Emit this event to notify the viewer that the editing mode changed.
	 *
	 * @param editing Whether the viewer is now in editing mode
	 */
	'update:editing': [boolean]
}

/**
 * Options for opening the viewer
 */
export type ViewerOptions = {
	/**
	 * Will be called to append more files when reaching the end of the current list
	 */
	loadMore?: () => Promise<IFile[]>

	/**
	 * Called when navigating to the previous item, with the file navigated to.
	 */
	onPrev?: (file: IFile) => void

	/**
	 * Called when navigating to the next item, with the file navigated to.
	 */
	onNext?: (file: IFile) => void

	/**
	 * Called when the viewer is closed
	 */
	onClose?: () => void

	/**
	 * Whether to open straight into editing mode (e.g. from an `editing=true` URL).
	 * Ignored for handlers that do not support editing.
	 */
	editing?: boolean

	/**
	 * Called when the editing state changes, so the opener can reflect it (e.g.
	 * in the URL).
	 */
	onEditingChange?: (editing: boolean) => void

	/**
	 * Whether the viewer can loop from last to first item and vice versa. Defaults to true.
	 */
	canLoop?: boolean

	/**
	 * The files view the viewer was opened from. Forwarded to the file actions
	 * rendered inside the viewer (download, delete, details, …) so they can run
	 * with the same context as in the files list.
	 */
	view?: IView

	/**
	 * The folder the opened files live in. Forwarded to those file actions.
	 */
	folder?: IFolder
}

const defaultViewerOptions: ViewerOptions = {
	loadMore: async () => [],
	onPrev: () => {},
	onNext: () => {},
	onClose: () => {},
	canLoop: true,
}

export interface ViewerAPI {
	open(nodes: IFile[], file?: IFile, options?: ViewerOptions, handlerId?: string): Promise<void>
	openFolder(folder: IFolder, file?: IFile, options?: ViewerOptions, handlerId?: string): Promise<void>
	compare(node1: IFile, node2: IFile, handlerId?: string): Promise<void>

	/**
	 * Show an already-opened file by its id, without triggering navigation
	 * callbacks. Used to sync the viewer to browser history (back/forward).
	 *
	 * @param fileid - The id of the file to show
	 */
	goTo(fileid: number): void

	/**
	 * Close the viewer.
	 */
	close(): void

	/**
	 * Set the editing state (used to sync with the `editing` URL param).
	 *
	 * @param editing - Whether the viewer should be in editing mode
	 */
	setEditing(editing: boolean): void
}

export class Viewer extends EventTarget implements ViewerAPI {
	private viewer: InstanceType<typeof ViewerVue> | null = null

	/**
	 * Set the viewer instance (called from init.ts)
	 * Private, do not use directly.
	 *
	 * @param viewer - The mounted Viewer Vue component instance
	 */
	_setViewer(viewer: InstanceType<typeof ViewerVue>) {
		this.viewer = viewer
	}

	async open(nodes: IFile[], file?: IFile, options: ViewerOptions = defaultViewerOptions, handlerId?: string): Promise<void> {
		if (!this.viewer) {
			throw new Error('Viewer is not initialized')
		}
		this.viewer.open(nodes, file, options, handlerId)
	}

	async openFolder(folder: IFolder, file?: IFile, options: ViewerOptions = defaultViewerOptions, handlerId?: string): Promise<void> {
		if (!this.viewer) {
			throw new Error('Viewer is not initialized')
		}
		this.viewer.openFolder(folder, file, options, handlerId)
	}

	async compare(node1: IFile, node2: IFile, handlerId?: string): Promise<void> {
		if (!this.viewer) {
			throw new Error('Viewer is not initialized')
		}
		this.viewer.compare(node1, node2, handlerId)
	}

	goTo(fileid: number): void {
		this.viewer?.goTo(fileid)
	}

	close(): void {
		this.viewer?.close()
	}

	setEditing(editing: boolean): void {
		this.viewer?.setEditing(editing)
	}
}

/**
 * Get the shared viewer instance, creating it on first use.
 */
export function getViewer(): Viewer {
	return window._oca_viewer_service ??= new Viewer()
}
