<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcModal
		ref="modal"
		:additionalTrapElements="trapElements"
		:clearViewDelay="-1 /* disable fade-out because of accessibility reasons */"
		:closeButtonOutside="true"
		:dark="true"
		:data-handler="currentHandler?.id"
		:disableSwipe="!canSwipe && editing"
		:enableSlideshow="!isComparing && (hasPrevious || hasNext)"
		:hasNext="!isComparing && hasNext"
		:hasPrevious="!isComparing && hasPrevious"
		:inlineActions="canEdit ? 1 : 0"
		:lightBackdrop="lightBackdrop"
		:name="modalName"
		:show="!!currentFile"
		:slideshowPaused="editing"
		:spreadNavigation="true"
		:style="{ width: isSidebarShown ? `${sidebarPosition}px` : null }"
		class="viewer__modal"
		size="full"
		@close="close"
		@previous="previous"
		@next="next">
		<!-- Header actions -->
		<template #actions>
			<!-- Internal edit action, handled by the handler itself -->
			<NcActionButton
				v-if="currentHandler?.canEdit && !editing"
				closeAfterClick
				@click="editing = true">
				<template #icon>
					<PencilIcon :size="20" />
				</template>
				{{ t('viewer', 'Edit') }}
			</NcActionButton>

			<!-- Open sidebar for the current file -->
			<NcActionButton
				v-if="!isSidebarShown && !!currentFile"
				closeAfterClick
				@click="showSidebar">
				<template #icon>
					<DockRight :size="20" />
				</template>
				{{ t('viewer', 'Open sidebar') }}
			</NcActionButton>

			<!-- Files actions available for the current file (download, delete, …).
			     Top-level actions, unless a submenu (e.g. "Set reminder") is open. -->
			<template v-if="!openedSubmenu">
				<NcActionButton
					v-for="action in fileActions"
					:key="action.id"
					:isMenu="isValidMenu(action)"
					:closeAfterClick="!isValidMenu(action)"
					@click="onActionClick(action)">
					<template #icon>
						<NcIconSvgWrapper :svg="actionIcon(action)" :size="20" />
					</template>
					{{ actionLabel(action) }}
				</NcActionButton>
			</template>

			<!-- Open submenu: a back entry followed by the parent's children -->
			<template v-else>
				<NcActionButton @click="onBackToMenuClick">
					<template #icon>
						<ChevronLeft :size="20" />
					</template>
					{{ actionLabel(openedSubmenu) }}
				</NcActionButton>
				<NcActionButton
					v-for="action in enabledSubmenuActions[openedSubmenu.id]"
					:key="action.id"
					closeAfterClick
					@click="handleAction(action)">
					<template #icon>
						<NcIconSvgWrapper :svg="actionIcon(action)" :size="20" />
					</template>
					{{ actionLabel(action) }}
				</NcActionButton>
			</template>
		</template>

		<!-- Loading overlay, shown on top of the (mounted but hidden) handler -->
		<span v-if="loading && !errorString" class="viewer__loading">
			<NcLoadingIcon :appearance="lightBackdrop ? 'dark' : 'light'" :size="32" />
		</span>

		<!-- Error message -->
		<NcEmptyContent
			v-else-if="errorString"
			:name="errorString"
			:description="t('viewer', 'We were unable to display the requested file.')">
			<template #icon>
				<FileAlertOutlineIcon />
			</template>
		</NcEmptyContent>

		<!--
			The handler is always mounted while a file is set (only hidden with
			v-show while loading or on error) so that it can actually load and
			emit its `loaded` event. It must not share the v-if chain with the
			loading spinner, otherwise it would never mount and never load.
		-->
		<!-- Comparison of two files, rendered side by side -->
		<div
			v-if="isComparing"
			v-show="!loading && !errorString"
			class="viewer__comparison">
			<component
				:is="currentHandler?.tagname"
				v-if="currentFile"
				:file="currentFile"
				:files="[]"
				:isSidebarShown="isSidebarShown"
				:max-height="height"
				:maxWidth="width / 2"
				:editing="false"
				@loaded="onLoad"
				@errored="onError" />
			<component
				:is="comparisonHandler?.tagname"
				v-if="comparisonFile"
				:file="comparisonFile"
				:files="[]"
				:isSidebarShown="isSidebarShown"
				:max-height="height"
				:maxWidth="width / 2"
				:editing="false"
				@loaded="onLoad"
				@errored="onError" />
		</div>

		<!-- Single file view -->
		<component
			:is="currentHandler?.tagname"
			v-else-if="currentFile"
			v-show="!loading && !errorString"
			:key="`${currentFile.fileid}-${reloadKey}`"
			v-model:canSwipe="canSwipe"
			:file="currentFile"
			:files="currentFileList"
			:isSidebarShown="isSidebarShown"
			:max-height="height"
			:maxWidth="width"
			:editing="editing"
			:localSource="editedSources[currentFile.fileid!]"
			@loaded="onLoad"
			@errored="onError" />
	</NcModal>

	<!-- Editing overlay, rendered at the viewer level (not inside the handler
	     custom element) so its close/save events reach the viewer directly. -->
	<ImageEditor
		v-if="editing && currentFile && currentHandler?.canEdit"
		:file="currentFile"
		@saved="onEditSaved"
		@close="editing = false" />

	<!-- In-viewer rename dialog: the Files rename action edits the file-list row,
	     which the viewer does not host, so we rename here instead. -->
	<NcDialog
		:open="renameDialogOpen"
		:name="t('viewer', 'Rename file')"
		size="small"
		:buttons="renameButtons"
		@update:open="renameDialogOpen = $event">
		<form @submit.prevent="submitRename">
			<NcTextField
				v-model="renameValue"
				:label="t('viewer', 'New name')"
				labelVisible />
		</form>
	</NcDialog>
</template>

<script setup lang="ts">
import type { IFile, IFolder, INode, IView } from '@nextcloud/files'
import type { IFileAction } from '@nextcloud/files'
import type { IHandler } from '../api_package/index.ts'
import type { ViewerAPI, ViewerOptions } from '../api_package/viewer.ts'

import { showError } from '@nextcloud/dialogs'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import { FileType } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import debounce from 'debounce'
import { computed, defineAsyncComponent, onMounted, onUnmounted, ref, triggerRef, useTemplateRef, watch } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcModal from '@nextcloud/vue/components/NcModal'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import ChevronLeft from 'vue-material-design-icons/ChevronLeft.vue'
import DockRight from 'vue-material-design-icons/DockRight.vue'
import FileAlertOutlineIcon from 'vue-material-design-icons/FileAlertOutline.vue'
import PencilIcon from 'vue-material-design-icons/Pencil.vue'
import { getHandlers } from '../api_package/index.ts'
import { useViewerActions } from '../composables/useViewerActions.ts'
import { getHandlerForFile } from '../helpers/handlerHelper.ts'
import { fetchFolderContent } from '../services/dav.ts'
import { logger } from '../services/logger.ts'
import { renameFile } from '../utils/rename.ts'

defineOptions({ name: 'ViewerModal' })

// The image editor is a large, canvas-based dependency; load it only when needed.
const ImageEditor = defineAsyncComponent(() => import('../components/ImageEditor.vue'))

let resizeObserver = null as ResizeObserver | null
const modal = useTemplateRef<{ $el: HTMLElement }>('modal')
const height = ref(0)
const width = ref(0)

// State
const loading = ref(true)
const errorString = ref<string | null>(null)
// Number of handler components still loading before the spinner is hidden.
// 1 for a normal open, 2 while comparing two files side by side.
const pendingLoads = ref(0)
// Bumped to force the current handler to remount (e.g. after an edit save).
const reloadKey = ref(0)
// Object URLs of freshly edited images, by file id, shown without refetching.
const editedSources = ref<Record<number, string>>({})

// Abilities
const canEdit = ref(true)
const canSwipe = ref(true)
const editing = ref(false)
const lightBackdrop = ref(false)

// Sidebar handling
const sidebarPosition = ref(0)
const isSidebarShown = computed(() => sidebarPosition.value > 0)
const trapElements = ref<Element[]>([])
/** Body class that expands the Files sidebar to full height next to the viewer. */
const SIDEBAR_FULLSCREEN_CLASS = 'viewer--sidebar-fullscreen'

// Current context
const currentFile = ref<IFile>()
const currentFileList = ref<IFile[]>([])
const currentHandler = ref<IHandler>()
const currentOptions = ref<ViewerOptions>({
	canLoop: true,
	onClose: () => {},
	onNext: () => {},
	onPrev: () => {},
	loadMore: () => Promise.resolve([]),
})

// Comparison context (compare API)
const comparisonFile = ref<IFile>()
const comparisonHandler = ref<IHandler>()
const isComparing = computed(() => !!comparisonFile.value)

// Files actions rendered in the viewer menu (download, delete, …), linked to
// the Files actions and run with the view/folder forwarded by the opener.
const { actions: fileActions, enabledSubmenuActions, isValidMenu, actionLabel, actionIcon, execAction } = useViewerActions(
	() => currentFile.value as IFile | undefined,
	() => currentFileList.value as IFile[],
	() => currentOptions.value.view as IView | undefined,
	() => currentOptions.value.folder as IFolder | undefined,
)

// The parent action whose submenu is currently open in the menu, if any.
const openedSubmenu = ref<IFileAction | null>(null)

// Stable Files action ids the viewer handles itself instead of delegating,
// because their default UI lives on the (hidden) file-list row.
const RENAME_ACTION_ID = 'rename'

// Rename dialog state
const renameDialogOpen = ref(false)
const renameValue = ref('')

/**
 * Handle a click on a menu action: open its submenu if it has one, otherwise
 * run it.
 *
 * @param action - The clicked file action
 */
function onActionClick(action: IFileAction) {
	if (isValidMenu(action)) {
		openedSubmenu.value = action
		return
	}
	handleAction(action)
}

/**
 * Go back from a submenu to the top-level menu.
 */
function onBackToMenuClick() {
	openedSubmenu.value = null
}

/**
 * Run a viewer menu action. The rename action is intercepted and handled with
 * an in-viewer dialog; everything else is delegated to the Files action.
 *
 * @param action - The file action to run
 */
function handleAction(action: IFileAction) {
	openedSubmenu.value = null
	if (action.id === RENAME_ACTION_ID) {
		openRenameDialog()
		return
	}
	execAction(action)
}

const renameButtons = computed(() => [
	{
		label: t('viewer', 'Cancel'),
		callback: () => {
			renameDialogOpen.value = false
		},
	},
	{
		label: t('viewer', 'Rename'),
		variant: 'primary' as const,
		callback: submitRename,
	},
])

/**
 * Open the rename dialog prefilled with the current file name.
 */
function openRenameDialog() {
	if (!currentFile.value) {
		return
	}
	renameValue.value = currentFile.value.basename
	renameDialogOpen.value = true
}

/**
 * Perform the rename of the current file.
 */
async function submitRename() {
	const file = currentFile.value
	if (!file) {
		return
	}
	try {
		await renameFile(file, renameValue.value)
		// The node is mutated in place; force the header/name to re-read it.
		triggerRef(currentFile)
		triggerRef(currentFileList)
		renameDialogOpen.value = false
	} catch (error) {
		showError((error as Error).message)
	}
}

/**
 * When a file is deleted (by the delete action or elsewhere), drop it from the
 * viewer list and move to the next file, then the previous, then close if the
 * list is now empty.
 *
 * @param node - The deleted node
 */
function onNodeDeleted(node: INode) {
	const index = currentFileList.value.findIndex((file) => file.fileid === node.fileid || file.source === node.source)
	if (index === -1) {
		return
	}

	const wasCurrent = currentFileList.value[index]?.fileid === currentFile.value?.fileid
	currentFileList.value = currentFileList.value.filter((_, i) => i !== index)

	if (!wasCurrent) {
		return
	}

	if (currentFileList.value.length === 0) {
		close()
		return
	}

	// Same index now points to the former next file; clamp to the last one when
	// the deleted file was at the end (i.e. fall back to the previous file).
	const newFile = currentFileList.value[Math.min(index, currentFileList.value.length - 1)] as IFile
	currentHandler.value = getHandlerForFile(newFile)
	currentFile.value = newFile
	preloadNeighbors()
}

/**
 * When the shown file is updated (e.g. saved from the image editor), remount the
 * handler so it re-reads the node and shows the new content.
 *
 * @param node - The updated node
 */
function onNodeUpdated(node: INode) {
	// A freshly edited file is already shown from its local blob; do not refetch.
	if (node.fileid !== undefined && editedSources.value[node.fileid]) {
		return
	}
	if (node.fileid === currentFile.value?.fileid) {
		reloadKey.value++
	}
}

/**
 * Show a just-saved edited image from its local object URL (no server refetch).
 *
 * @param source - The edited image as an object URL
 */
function onEditSaved(source: string) {
	const fileid = currentFile.value?.fileid
	if (fileid === undefined) {
		return
	}
	// Release a previous edit of the same file before replacing it.
	const previous = editedSources.value[fileid]
	if (previous) {
		URL.revokeObjectURL(previous)
	}
	editedSources.value = { ...editedSources.value, [fileid]: source }
}

/**
 * Release every edited-image object URL and clear the map.
 */
function clearEditedSources() {
	for (const url of Object.values(editedSources.value)) {
		URL.revokeObjectURL(url)
	}
	editedSources.value = {}
}

/**
 * Set the editing state, used to sync the viewer with the `editing` URL param
 * (e.g. on browser back/forward). Only handlers that support editing can enter it.
 *
 * @param value - Whether the viewer should be in editing mode
 */
function setEditing(value: boolean) {
	editing.value = value && Boolean(currentHandler.value?.canEdit)
}

// Reflect editing changes (Edit button, editor save/cancel) in the URL so a
// refresh reopens in the same state.
watch(editing, (value) => {
	currentOptions.value.onEditingChange?.(value)
})

const modalName = computed(() => {
	if (isComparing.value) {
		return t('viewer', 'Comparing {file1} and {file2}', {
			file1: currentFile.value?.basename ?? '',
			file2: comparisonFile.value?.basename ?? '',
		})
	}
	return currentFile.value?.basename || ''
})

const hasNext = computed(() => {
	const canLoop = currentOptions.value.canLoop ?? true
	const currentIndex = currentFileList.value.findIndex((f) => f === currentFile.value)
	if (currentIndex === -1) {
		return false
	}

	// If we are not allowed to loop and we are at the end,
	// we cannot go next
	if (currentIndex < currentFileList.value.length - 1) {
		return true
	}

	// If we are allowed to loop and we are at the end,
	// we can go next if there is more than one file
	if (canLoop && currentIndex === currentFileList.value.length - 1 && currentFileList.value.length > 1) {
		return true
	}

	return false
})
const hasPrevious = computed(() => {
	const canLoop = currentOptions.value.canLoop ?? true
	const currentIndex = currentFileList.value.findIndex((f) => f === currentFile.value)
	if (currentIndex === -1) {
		return false
	}

	// If we are not allowed to loop and we are at the start,
	// we cannot go previous
	if (currentIndex > 0) {
		return true
	}

	// If we are allowed to loop and we are at the start,
	// we can go previous if there is more than one file
	if (canLoop && currentIndex === 0 && currentFileList.value.length > 1) {
		return true
	}

	return false
})

const open: ViewerAPI['open'] = async (files, file, options, handlerId) => {
	logger.debug('Opening files', { files, file, options, handlerId })
	loading.value = true

	// Filter out any non-file files
	files = files.filter((n) => n.type === FileType.File)

	// Ensure we have at least one file to open
	if (files.length === 0 && !file) {
		logger.error('No files provided to open')
		errorString.value = t('viewer', 'No files were provided to open.')
		return
	}

	if (handlerId && !getHandlers().has(handlerId)) {
		logger.error('There is no handler matching the given handler ID')
		errorString.value = t('viewer', 'There was no plugin available to open this file.')
		return
	}

	// Slight adjustment: if there is a mismatch between
	// the provided file and the list of files
	if (!file) {
		file = files[0]
	} else if (!files.includes(file)) {
		files = [file, ...files]
	}

	// Last check, we need to have something to open
	if (!file) {
		logger.error('No file provided to open')
		errorString.value = t('viewer', 'No files were provided to open.')
		return
	}

	const handler = handlerId ? getHandlers().get(handlerId) : getHandlerForFile(file)
	if (!handler) {
		logger.error('No handler found for the given file', { file, files })
		errorString.value = t('viewer', 'There was no plugin available to open this file.')
		return
	}

	/**
	 * Let's compute the current file list based on the current handler
	 * and its group. We only want to show files that can be handled
	 * by the same handler or handlers from the same group.
	 */
	currentFileList.value = files.filter((f) => {
		const h = getHandlerForFile(f)
		const group = h?.group
		return group === handler.group || h?.id === handler.id
	})

	if (currentFileList.value.length === 0) {
		// Fallback to just the provided file
		currentFileList.value = [file]
	}

	if (currentFileList.value.length !== files.length) {
		logger.debug(`Found ${currentFileList.value.length} files for the current handler/group out of ${files.length} provided files`, {
			filtered: currentFileList.value,
			provided: files,
		})
	}

	comparisonFile.value = undefined
	comparisonHandler.value = undefined
	currentHandler.value = handler
	currentFile.value = file
	currentOptions.value = options ?? {} as ViewerOptions
	pendingLoads.value = 1
	// Open straight into edit mode when requested (e.g. from an `editing=true` URL).
	editing.value = Boolean(options?.editing) && Boolean(handler.canEdit)

	onOpen()
	preloadNeighbors()
}

const openFolder: ViewerAPI['openFolder'] = async (folder, file, options, handlerId) => {
	logger.debug('Opening folder', { folder, file, options, handlerId })
	loading.value = true

	if (handlerId && !getHandlers().has(handlerId)) {
		logger.error('There is no handler matching the given handler ID')
		errorString.value = t('viewer', 'We were not able to open the file.')
		return
	}

	if (!folder || folder.type !== FileType.Folder) {
		logger.error('The provided folder is not a directory', { folder })
		errorString.value = t('viewer', 'We were not able to open the file.')
		return
	}

	try {
		const files = await fetchFolderContent(folder)
		return open(files, file, options, handlerId)
	} catch (error) {
		logger.error('Failed to fetch folder contents', { folder, error })
		errorString.value = t('viewer', 'We were not able to open the file.')
		return
	}
}

const compare: ViewerAPI['compare'] = async (file1, file2, handlerId) => {
	logger.debug('Comparing files', { file1, file2, handlerId })
	loading.value = true

	if (handlerId && !getHandlers().has(handlerId)) {
		logger.error('There is no handler matching the given handler ID')
		errorString.value = t('viewer', 'We were not able to open the file.')
		return
	}

	if (!file1 || !file2 || file1.type !== FileType.File || file2.type !== FileType.File) {
		logger.error('Two files are required to compare', { file1, file2 })
		errorString.value = t('viewer', 'We were not able to open the file.')
		return
	}

	const handler1 = handlerId ? getHandlers().get(handlerId) : getHandlerForFile(file1)
	const handler2 = handlerId ? getHandlers().get(handlerId) : getHandlerForFile(file2)
	if (!handler1 || !handler2) {
		logger.error('No handler found for one of the files to compare', { file1, file2 })
		errorString.value = t('viewer', 'There was no plugin available to open this file.')
		return
	}

	// Comparison mode has no navigation, so we reset the slideshow context
	currentFileList.value = []
	currentOptions.value = {} as ViewerOptions
	currentHandler.value = handler1
	currentFile.value = file1
	comparisonHandler.value = handler2
	comparisonFile.value = file2
	pendingLoads.value = 2

	onOpen()
}

/**
 * Handle Viewer opening to determine backdrop style
 */
function onOpen() {
	// Determine if we should use a light backdrop
	const backgroundInvertIfDark = getComputedStyle(document.documentElement).getPropertyValue('--background-invert-if-dark')
	const defaultThemeIsLight = backgroundInvertIfDark.trim() !== 'invert(100%)'
	const theme = currentHandler.value?.theme ?? 'default'
	lightBackdrop.value = theme === 'light' || (theme === 'default' && defaultThemeIsLight)
}

/**
 * Preload the previous and next files so navigation feels instant.
 * Uses the handler's optional preload function.
 */
function preloadNeighbors() {
	const currentIndex = currentFileList.value.findIndex((f) => f === currentFile.value)
	if (currentIndex === -1) {
		return
	}

	const neighbors = [
		currentFileList.value[currentIndex - 1],
		currentFileList.value[currentIndex + 1],
	].filter((f): f is IFile => Boolean(f))

	for (const node of neighbors) {
		const handler = getHandlerForFile(node)
		if (!handler?.preload) {
			continue
		}
		handler.preload(node).catch((error) => {
			logger.debug('Failed to preload neighbor file', { node, error })
		})
	}
}

/**
 * Handle successful loading of the current file
 * This is emitted by the handler web component
 */
function onLoad() {
	errorString.value = null
	pendingLoads.value = Math.max(0, pendingLoads.value - 1)
	if (pendingLoads.value === 0) {
		loading.value = false
	}
}

/**
 * Handle error while loading the current file
 * This is emitted by the handler web component
 *
 * @param error The error that occurred
 */
function onError(error: Error) {
	logger.error('Error while loading file in viewer', { error })
	loading.value = false
	pendingLoads.value = 0
	errorString.value = error.message || t('viewer', 'An unknown error occurred while loading the file.')
}

/**
 * Close the viewer and reset state
 */
function close() {
	// Leave editing first so its URL param is stripped while onEditingChange is
	// still wired (before currentOptions is reset below).
	editing.value = false
	currentOptions.value.onClose?.()
	currentFile.value = undefined
	currentFileList.value = []
	currentHandler.value = undefined
	comparisonFile.value = undefined
	comparisonHandler.value = undefined
	currentOptions.value = {} as ViewerOptions
	errorString.value = null
	// Reset transient UI state so it never leaks into the next open
	loading.value = true
	canSwipe.value = true
	pendingLoads.value = 0
	openedSubmenu.value = null
	clearEditedSources()
	// Restore the app header when closing (the sidebar may still be open).
	document.body.classList.remove(SIDEBAR_FULLSCREEN_CLASS)
}

/**
 * Go to the next file in the list if possible
 */
async function next() {
	const canLoop = currentOptions.value.canLoop ?? true
	const currentIndex = currentFileList.value.findIndex((f) => f === currentFile.value)
	let newIndex = currentIndex + 1

	if (currentIndex === -1) {
		logger.error('Current file not found in the file list', { currentFile: currentFile.value, fileList: currentFileList.value })
		return
	}

	// If we are not allowed to loop and we are at the end, do nothing
	if (!canLoop && currentIndex >= currentFileList.value.length - 1) {
		// We are at the end and cannot loop, do nothing
		return
	}

	// If we are allowed to loop and we are at the end, go to the start
	if (canLoop && newIndex >= currentFileList.value.length) {
		newIndex = 0
	}

	const newFile = currentFileList.value[newIndex] as IFile
	// Should not happen™, but just in case
	if (!newFile) {
		logger.error('Next file not found in the file list', { newIndex, fileList: currentFileList.value })
		return
	}

	currentHandler.value = getHandlerForFile(newFile)
	currentFile.value = newFile
	currentOptions.value.onNext?.(newFile)

	// If we are at the end of the list, try to load more files if possible
	if (newIndex === currentFileList.value.length - 1) {
		try {
			const moreFiles = await currentOptions.value.loadMore?.() ?? []
			if (moreFiles.length > 0) {
				currentFileList.value = currentFileList.value.concat(moreFiles)
			}
		} catch (error) {
			logger.error('Failed to load more files', { error })
		}
	}

	preloadNeighbors()
}

/**
 * Go to the previous file in the list if possible
 */
function previous() {
	const canLoop = currentOptions.value.canLoop ?? true
	const currentIndex = currentFileList.value.findIndex((f) => f === currentFile.value)
	let newIndex = currentIndex - 1

	if (currentIndex === -1) {
		logger.error('Current file not found in the file list', { currentFile: currentFile.value, fileList: currentFileList.value })
		return
	}

	// If we are not allowed to loop and we are at the start, do nothing
	if (!canLoop && currentIndex <= 0) {
		// We are at the start and cannot loop, do nothing
		return
	}

	// If we are allowed to loop and we are at the start, go to the end
	if (canLoop && newIndex < 0) {
		newIndex = currentFileList.value.length - 1
	}

	const newFile = currentFileList.value[newIndex] as IFile
	// Should not happen™, but just in case
	if (!newFile) {
		logger.error('Previous file not found in the file list', { newIndex, fileList: currentFileList.value })
		return
	}

	currentHandler.value = getHandlerForFile(newFile)
	currentFile.value = newFile
	currentOptions.value.onPrev?.(newFile)

	preloadNeighbors()
}

/**
 * Show an already-loaded file by its id without firing navigation callbacks.
 * Used to sync the viewer to the browser history (back/forward) so the opener
 * never pushes a new history entry for a move it triggered itself.
 *
 * @param fileid - The id of the file to show
 */
function goTo(fileid: number) {
	const newFile = currentFileList.value.find((f) => f.fileid === fileid)
	if (!newFile) {
		logger.warn('Cannot go to file, not in the current list', { fileid })
		return
	}
	if (newFile === currentFile.value) {
		return
	}

	currentHandler.value = getHandlerForFile(newFile)
	currentFile.value = newFile
	preloadNeighbors()
}

/**
 * Open the Files sidebar for the current file.
 */
function showSidebar() {
	if (!currentFile.value) {
		return
	}

	// The Files app sidebar store subscribes to this event and opens
	// the sidebar for the file identified by its dav source.
	emit('viewer:sidebar:open', { source: currentFile.value.source })
}

/**
 * Handle app sidebar opening to adjust viewer size
 */
function onAppSidebarOpen() {
	const sidebar = document.querySelector('aside.app-sidebar')
	if (sidebar) {
		sidebarPosition.value = sidebar.getBoundingClientRect().left
		trapElements.value = [sidebar]
	}
	// Only expand the sidebar to full height when the viewer is actually open;
	// the sidebar is also opened from the plain files list, where the app header
	// must stay visible.
	if (currentFile.value) {
		document.body.classList.add(SIDEBAR_FULLSCREEN_CLASS)
	}
}

/**
 * Reset viewer size to default when app sidebar is closed
 */
function onAppSidebarClose() {
	sidebarPosition.value = 0
	trapElements.value = []
	document.body.classList.remove(SIDEBAR_FULLSCREEN_CLASS)
}

/**
 * Close viewer when clicking outside of the modal content
 *
 * @param event The mouse event
 */
function onClickOutside(event: Event) {
	// check if we clicked on the modal container directly and not on its children
	const modalContent = modal.value?.$el?.querySelector('.modal-container__content')
	if (event.target === modalContent) {
		logger.debug('Clicked outside the viewer, closing viewer')
		close()
	}
}

/**
 * Update viewer dimensions on window resize
 */
function onViewerResize() {
	const modalContainer = modal.value?.$el?.querySelector('.modal-container')
	height.value = modalContainer?.clientHeight || 0
	width.value = modalContainer?.clientWidth || 0
	logger.debug('Screen resized, updating viewer dimensions', { height: height.value, width: width.value })
}

// Listen to Viewer file changes to trigger resize
watch(currentFile, (newFile, oldFile) => {
	// A submenu belongs to the previous file's action set; never carry it over.
	openedSubmenu.value = null
	if (newFile && !oldFile) {
		onViewerResize()
	}
})

onMounted(() => {
	resizeObserver = new ResizeObserver(debounce(() => {
		onViewerResize()
	}, 100))

	if (!modal?.value?.$el) {
		logger.error('Modal element not found in Viewer onMounted')
		return
	}

	// Observe viewer size changes
	resizeObserver.observe(modal.value.$el)
	logger.debug('Resize observer initialized for viewer')

	const modalContent = modal.value?.$el?.querySelector('.modal-container__content')
	if (modalContent) {
		modalContent.addEventListener('click', onClickOutside)
	}

	// React to the Files app sidebar to resize the viewer accordingly
	subscribe('files:sidebar:opened', onAppSidebarOpen)
	subscribe('files:sidebar:closed', onAppSidebarClose)

	// Advance the viewer when the shown file is deleted (from the menu or elsewhere)
	subscribe('files:node:deleted', onNodeDeleted)
	subscribe('files:node:updated', onNodeUpdated)
})

onUnmounted(() => {
	resizeObserver?.disconnect()
	unsubscribe('files:sidebar:opened', onAppSidebarOpen)
	unsubscribe('files:sidebar:closed', onAppSidebarClose)
	unsubscribe('files:node:deleted', onNodeDeleted)
	unsubscribe('files:node:updated', onNodeUpdated)
	document.body.classList.remove(SIDEBAR_FULLSCREEN_CLASS)

	const modalContent = modal.value?.$el?.querySelector('.modal-container__content')
	if (modalContent) {
		modalContent.removeEventListener('click', onClickOutside)
	}
})

defineExpose<ViewerAPI>({
	open,
	openFolder,
	compare,
	goTo,
	close,
	setEditing,
})
</script>

<style scoped lang="scss">
.viewer__modal {
	:deep(.modal-container__content) {
		display: flex;
		justify-content: center;
		align-items: center;
	}

	:deep(.modal-container) {
		top: var(--header-height) !important;
		bottom: var(--header-height) !important;
		height: auto !important;
		background-color: transparent !important;
		box-shadow: none !important;
	}
}

.viewer__comparison {
	display: flex;
	flex-direction: row;
	justify-content: center;
	align-items: center;
	gap: 8px;
	width: 100%;
	height: 100%;
}

</style>

<!-- Unscoped: when the sidebar is shown next to the viewer, pin it and hide the
     app header so it fills the full height (as the pre-7.0.0 viewer did). -->
<style lang="scss">
body.viewer--sidebar-fullscreen {
	#app-sidebar-vue {
		position: fixed;
		width: calc(var(--app-sidebar-width) + var(--body-container-margin));
	}

	.app-navigation ~ #app-content-vue:has(~ #app-sidebar-vue:not([style*="display: none"])) {
		flex-basis: calc(100% - 300px - clamp(300px, 27vw, 500px));
	}

	#app-content-vue:first-child:has(~ #app-sidebar-vue:not([style*="display: none"])),
	.app-navigation--close ~ #app-content-vue:has(~ #app-sidebar-vue:not([style*="display: none"])),
	.app-navigation--closed ~ #app-content-vue:has(~ #app-sidebar-vue:not([style*="display: none"])) {
		flex-basis: calc(100% - clamp(300px, 27vw, 500px));
	}

	#header {
		visibility: hidden;
	}
}
</style>
