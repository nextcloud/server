/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { FileAction } from '@nextcloud/files'
import type { PropType } from 'vue'
import type { FileSource } from '../types.ts'

import { showError } from '@nextcloud/dialogs'
import { FileType, Folder, getFileActions, File as NcFile, Node, NodeStatus, Permission } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { isPublicShare } from '@nextcloud/sharing/public'
import { vOnClickOutside } from '@vueuse/components'
import { extname } from 'path'
import Vue, { computed, defineComponent } from 'vue'
import { action as sidebarAction } from '../actions/sidebarAction.ts'
import logger from '../logger.ts'
import { dataTransferToFileTree, onDropExternalFiles, onDropInternalFiles } from '../services/DropService.ts'
import { getDragAndDropPreview } from '../utils/dragUtils.ts'
import { hashCode } from '../utils/hashUtils.ts'
import { isDownloadable } from '../utils/permissions.ts'

Vue.directive('onClickOutside', vOnClickOutside)

const actions = getFileActions()

export default defineComponent({
	props: {
		source: {
			type: [Folder, NcFile, Node] as PropType<Node>,
			required: true,
		},
		nodes: {
			type: Array as PropType<Node[]>,
			required: true,
		},
		filesListWidth: {
			type: Number,
			default: 0,
		},
		isMtimeAvailable: {
			type: Boolean,
			default: false,
		},
		compact: {
			type: Boolean,
			default: false,
		},
	},

	provide() {
		return {
			defaultFileAction: computed(() => this.defaultFileAction),
			enabledFileActions: computed(() => this.enabledFileActions),
		}
	},

	data() {
		return {
			dragover: false,
			gridMode: false,
		}
	},

	computed: {
		fileid() {
			return this.source.fileid ?? 0
		},

		uniqueId() {
			return hashCode(this.source.source)
		},

		isLoading() {
			return this.source.status === NodeStatus.LOADING
		},

		/**
		 * The display name of the current node
		 * Either the nodes filename or a custom display name (e.g. for shares)
		 */
		displayName() {
			// basename fallback needed for apps using old `@nextcloud/files` prior 3.6.0
			return this.source.displayname || this.source.basename
		},
		/**
		 * The display name without extension
		 */
		basename() {
			if (this.extension === '') {
				return this.displayName
			}
			return this.displayName.slice(0, 0 - this.extension.length)
		},
		/**
		 * The extension of the file
		 */
		extension() {
			if (this.source.type === FileType.Folder) {
				return ''
			}

			return extname(this.displayName)
		},

		draggingFiles() {
			return this.draggingStore.dragging as FileSource[]
		},
		selectedFiles() {
			return this.selectionStore.selected as FileSource[]
		},
		isSelected() {
			return this.selectedFiles.includes(this.source.source)
		},

		isRenaming() {
			return this.renamingStore.renamingNode === this.source
		},
		isRenamingSmallScreen() {
			return this.isRenaming && this.filesListWidth < 512
		},

		isActive() {
			// Not using activeNode here because we want to
			// be reactive to the url change directly
			return String(this.fileid) === String(this.currentRouteFileId)
		},

		/**
		 * Check if the source is in a failed state after an API request
		 */
		isFailedSource() {
			return this.source.status === NodeStatus.FAILED
		},

		canDrag(): boolean {
			if (this.isRenaming) {
				return false
			}

			// Ignore if the node is not available
			if (this.isFailedSource) {
				return false
			}

			const canDrag = (node: Node): boolean => {
				return (node?.permissions & Permission.UPDATE) !== 0
			}

			// If we're dragging a selection, we need to check all files
			if (this.selectedFiles.length > 0) {
				const nodes = this.selectedFiles.map((source) => this.filesStore.getNode(source)) as Node[]
				return nodes.every(canDrag)
			}
			return canDrag(this.source)
		},

		canDrop(): boolean {
			if (this.source.type !== FileType.Folder) {
				return false
			}

			// Ignore if the node is not available
			if (this.isFailedSource) {
				return false
			}

			// If the current folder is also being dragged, we can't drop it on itself
			if (this.draggingFiles.includes(this.source.source)) {
				return false
			}

			return (this.source.permissions & Permission.CREATE) !== 0
		},

		openedMenu: {
			get() {
				return this.actionsMenuStore.opened === this.uniqueId.toString()
			},
			set(opened) {
				// If the menu is opened on another file entry, we ignore closed events
				if (opened === false && this.actionsMenuStore.opened !== this.uniqueId.toString()) {
					return
				}

				// If opened, we specify the current file id
				// else we set it to null to close the menu
				this.actionsMenuStore.opened = opened
					? this.uniqueId.toString()
					: null
			},
		},

		mtime() {
			// If the mtime is not a valid date, return it as is
			if (this.source.mtime && !isNaN(this.source.mtime.getDate())) {
				return this.source.mtime
			}

			if (this.source.crtime && !isNaN(this.source.crtime.getDate())) {
				return this.source.crtime
			}

			return null
		},

		mtimeOpacity() {
			if (!this.mtime) {
				return {}
			}

			// The time when we start reducing the opacity
			const maxOpacityTime = 31 * 24 * 60 * 60 * 1000 // 31 days
			// everything older than the maxOpacityTime will have the same value
			const timeDiff = Date.now() - this.mtime.getTime()
			if (timeDiff < 0) {
				// this means we have an invalid mtime which is in the future!
				return {}
			}

			// inversed time difference from 0 to maxOpacityTime (which would mean today)
			const opacityTime = Math.max(0, maxOpacityTime - timeDiff)
			// 100 = today, 0 = 31 days ago or older
			const percentage = Math.round(opacityTime * 100 / maxOpacityTime)
			return {
				color: `color-mix(in srgb, var(--color-main-text) ${percentage}%, var(--color-text-maxcontrast))`,
			}
		},

		/**
		 * Sorted actions that are enabled for this node
		 */
		enabledFileActions() {
			if (this.source.status === NodeStatus.FAILED) {
				return []
			}

			return actions
				.filter((action: FileAction) => {
					if (!action.enabled) {
						return true
					}

					// In case something goes wrong, since we don't want to break
					// the entire list, we filter out actions that throw an error.
					try {
						return action.enabled({
							nodes: [this.source],
							view: this.activeView,
							folder: this.activeFolder!,
							contents: this.nodes,
						})
					} catch (error) {
						logger.error('Error while checking action', { action, error })
						return false
					}
				})
				.sort((a, b) => (a.order || 0) - (b.order || 0))
		},

		defaultFileAction() {
			return this.enabledFileActions.find((action: FileAction) => action.default !== undefined)
		},
	},

	watch: {
		/**
		 * When the source changes, reset the preview
		 * and fetch the new one.
		 *
		 * @param newSource The new value of the source prop
		 * @param oldSource The previous value
		 */
		source(newSource: Node, oldSource: Node) {
			if (newSource.source !== oldSource.source) {
				this.resetState()
			}
		},

		openedMenu() {
			// Checking if the menu is really closed and not
			// just a change in the open state to another file entry.
			if (this.actionsMenuStore.opened === null) {
				// Reset any right menu position potentially set
				logger.debug('All actions menu closed, resetting right menu position...')
				const root = this.$el?.closest('main.app-content') as HTMLElement
				if (root !== null) {
					root.style.removeProperty('--mouse-pos-x')
					root.style.removeProperty('--mouse-pos-y')
				}
			}
		},
	},

	beforeDestroy() {
		this.resetState()
	},

	methods: {
		resetState() {
			// Reset the preview state
			this.$refs?.preview?.reset?.()

			// Close menu
			this.openedMenu = false
		},

		// Open the actions menu on right click
		onRightClick(event) {
			// If already opened, fallback to default browser
			if (this.openedMenu) {
				return
			}

			// Ignore right click if the node is not available
			if (this.isFailedSource) {
				return
			}

			// The grid mode is compact enough to not care about
			// the actions menu mouse position
			if (!this.gridMode) {
				// Actions menu is contained within the app content
				const root = this.$el?.closest('main.app-content') as HTMLElement
				const contentRect = root.getBoundingClientRect()
				// Using Math.min/max to prevent the menu from going out of the AppContent
				// 200 = max width of the menu
				logger.debug('Setting actions menu position...')
				root.style.setProperty('--mouse-pos-x', Math.max(0, event.clientX - contentRect.left - 200) + 'px')
				root.style.setProperty('--mouse-pos-y', Math.max(0, event.clientY - contentRect.top) + 'px')
			} else {
				// Reset any right menu position potentially set
				const root = this.$el?.closest('main.app-content') as HTMLElement
				root.style.removeProperty('--mouse-pos-x')
				root.style.removeProperty('--mouse-pos-y')
			}

			// If the clicked row is in the selection, open global menu
			const isMoreThanOneSelected = this.selectedFiles.length > 1
			this.actionsMenuStore.opened = this.isSelected && isMoreThanOneSelected ? 'global' : this.uniqueId.toString()

			// Prevent any browser defaults
			event.preventDefault()
			event.stopPropagation()
		},

		execDefaultAction(event: MouseEvent) {
			// Ignore click if we are renaming
			if (this.isRenaming) {
				return
			}

			// Ignore right click (button & 2) and any auxiliary button expect mouse-wheel (button & 4)
			if (Boolean(event.button & 2) || event.button > 4) {
				return
			}

			// Ignore if the node is not available
			if (this.isFailedSource) {
				return
			}

			// if ctrl+click / cmd+click (MacOS uses the meta key) or middle mouse button (button & 4), open in new tab
			// also if there is no default action use this as a fallback
			const metaKeyPressed = event.ctrlKey || event.metaKey || event.button === 1
			if (metaKeyPressed || !this.defaultFileAction) {
				// If no download permission, then we can not allow to download (direct link) the files
				if (!isDownloadable(this.source)) {
					return
				}

				const url = isPublicShare()
					? this.source.encodedSource
					: generateUrl('/f/{fileId}', { fileId: this.fileid })
				event.preventDefault()
				event.stopPropagation()

				// Open the file in a new tab if the meta key or the middle mouse button is clicked
				window.open(url, metaKeyPressed ? '_blank' : '_self')
				return
			}

			// every special case handled so just execute the default action
			event.preventDefault()
			event.stopPropagation()
			// Execute the first default action if any
			this.defaultFileAction.exec({
				nodes: [this.source],
				folder: this.activeFolder!,
				contents: this.nodes,
				view: this.activeView!,
			})
		},

		openDetailsIfAvailable(event) {
			event.preventDefault()
			event.stopPropagation()
			if (sidebarAction?.enabled?.({
				nodes: [this.source],
				folder: this.activeFolder!,
				contents: this.nodes,
				view: this.activeView!,
			})) {
				sidebarAction.exec({
					nodes: [this.source],
					folder: this.activeFolder!,
					contents: this.nodes,
					view: this.activeView!,
				})
			}
		},

		onDragOver(event: DragEvent) {
			this.dragover = this.canDrop
			if (!this.canDrop) {
				event.dataTransfer.dropEffect = 'none'
				return
			}

			// Handle copy/move drag and drop
			if (event.ctrlKey) {
				event.dataTransfer.dropEffect = 'copy'
			} else {
				event.dataTransfer.dropEffect = 'move'
			}
		},
		onDragLeave(event: DragEvent) {
			// Counter bubbling, make sure we're ending the drag
			// only when we're leaving the current element
			const currentTarget = event.currentTarget as HTMLElement
			if (currentTarget?.contains(event.relatedTarget as HTMLElement)) {
				return
			}

			this.dragover = false
		},

		async onDragStart(event: DragEvent) {
			event.stopPropagation()
			if (!this.canDrag || !this.fileid) {
				event.preventDefault()
				event.stopPropagation()
				return
			}

			logger.debug('Drag started', { event })

			// Make sure that we're not dragging a file like the preview
			event.dataTransfer?.clearData?.()

			// Reset any renaming
			this.renamingStore.$reset()

			// Dragging set of files, if we're dragging a file
			// that is already selected, we use the entire selection
			if (this.selectedFiles.includes(this.source.source)) {
				this.draggingStore.set(this.selectedFiles)
			} else {
				this.draggingStore.set([this.source.source])
			}

			const nodes = this.draggingStore.dragging
				.map((source) => this.filesStore.getNode(source)) as Node[]

			const image = await getDragAndDropPreview(nodes)
			event.dataTransfer?.setDragImage(image, -10, -10)
		},
		onDragEnd() {
			this.draggingStore.reset()
			this.dragover = false
			logger.debug('Drag ended')
		},

		async onDrop(event: DragEvent) {
			// skip if native drop like text drag and drop from files names
			if (!this.draggingFiles && !event.dataTransfer?.items?.length) {
				return
			}

			event.preventDefault()
			event.stopPropagation()

			// Caching the selection
			const selection = this.draggingFiles
			const items = [...event.dataTransfer?.items || []] as DataTransferItem[]

			// We need to process the dataTransfer ASAP before the
			// browser clears it. This is why we cache the items too.
			const fileTree = await dataTransferToFileTree(items)

			// We might not have the target directory fetched yet
			const contents = await this.activeView?.getContents(this.source.path)
			const folder = contents?.folder
			if (!folder) {
				showError(this.t('files', 'Target folder does not exist any more'))
				return
			}

			// If another button is pressed, cancel it. This
			// allows cancelling the drag with the right click.
			if (!this.canDrop || event.button) {
				return
			}

			const isCopy = event.ctrlKey
			this.dragover = false

			logger.debug('Dropped', { event, folder, selection, fileTree })

			// Check whether we're uploading files
			if (selection.length === 0 && fileTree.contents.length > 0) {
				await onDropExternalFiles(fileTree, folder, contents.contents)
				return
			}

			// Else we're moving/copying files
			const nodes = selection.map((source) => this.filesStore.getNode(source)) as Node[]
			await onDropInternalFiles(nodes, folder, contents.contents, isCopy)

			// Reset selection after we dropped the files
			// if the dropped files are within the selection
			if (selection.some((source) => this.selectedFiles.includes(source))) {
				logger.debug('Dropped selection, resetting select store...')
				this.selectionStore.reset()
			}
		},

		t,
	},
})
