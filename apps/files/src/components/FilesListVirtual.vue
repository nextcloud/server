<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<VirtualList ref="table"
		:data-component="userConfig.grid_view ? FileEntryGrid : FileEntry"
		:data-key="'source'"
		:data-sources="nodes"
		:grid-mode="userConfig.grid_view"
		:extra-props="{
			isMtimeAvailable,
			isSizeAvailable,
			nodes,
		}"
		:scroll-to-index="scrollToIndex"
		:caption="caption">
		<template #filters>
			<FileListFilters />
		</template>

		<template v-if="!isNoneSelected" #header-overlay>
			<span class="files-list__selected">{{ t('files', '{count} selected', { count: selectedNodes.length }) }}</span>
			<FilesListTableHeaderActions :current-view="currentView"
				:selected-nodes="selectedNodes" />
		</template>

		<template #before>
			<!-- Headers -->
			<FilesListHeader v-for="header in headers"
				:key="header.id"
				:current-folder="currentFolder"
				:current-view="currentView"
				:header="header" />
		</template>

		<!-- Thead-->
		<template #header>
			<!-- Table header and sort buttons -->
			<FilesListTableHeader ref="thead"
				:files-list-width="fileListWidth"
				:is-mtime-available="isMtimeAvailable"
				:is-size-available="isSizeAvailable"
				:nodes="nodes" />
		</template>

		<!-- Tfoot-->
		<template #footer>
			<FilesListTableFooter :current-view="currentView"
				:files-list-width="fileListWidth"
				:is-mtime-available="isMtimeAvailable"
				:is-size-available="isSizeAvailable"
				:nodes="nodes"
				:summary="summary" />
		</template>
	</VirtualList>
</template>

<script lang="ts">
import type { UserConfig } from '../types'
import type { Node as NcNode } from '@nextcloud/files'
import type { ComponentPublicInstance, PropType } from 'vue'
import type { Location } from 'vue-router'

import { Folder, Permission, View, getFileActions, FileType } from '@nextcloud/files'
import { showError } from '@nextcloud/dialogs'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { translate as t } from '@nextcloud/l10n'
import { useHotKey } from '@nextcloud/vue/composables/useHotKey'
import { defineComponent } from 'vue'

import { action as sidebarAction } from '../actions/sidebarAction.ts'
import { useActiveStore } from '../store/active.ts'
import { useFileListHeaders } from '../composables/useFileListHeaders.ts'
import { useFileListWidth } from '../composables/useFileListWidth.ts'
import { useRouteParameters } from '../composables/useRouteParameters.ts'
import { useSelectionStore } from '../store/selection.js'
import { useUserConfigStore } from '../store/userconfig.ts'

import FileEntry from './FileEntry.vue'
import FileEntryGrid from './FileEntryGrid.vue'
import FileListFilters from './FileListFilters.vue'
import FilesListHeader from './FilesListHeader.vue'
import FilesListTableFooter from './FilesListTableFooter.vue'
import FilesListTableHeader from './FilesListTableHeader.vue'
import FilesListTableHeaderActions from './FilesListTableHeaderActions.vue'
import VirtualList from './VirtualList.vue'
import logger from '../logger.ts'

export default defineComponent({
	name: 'FilesListVirtual',

	components: {
		FileListFilters,
		FilesListHeader,
		FilesListTableFooter,
		FilesListTableHeader,
		VirtualList,
		FilesListTableHeaderActions,
	},

	props: {
		currentView: {
			type: View,
			required: true,
		},
		currentFolder: {
			type: Folder,
			required: true,
		},
		nodes: {
			type: Array as PropType<NcNode[]>,
			required: true,
		},
		summary: {
			type: String,
			required: true,
		},
	},

	setup() {
		const activeStore = useActiveStore()
		const selectionStore = useSelectionStore()
		const userConfigStore = useUserConfigStore()

		const fileListWidth = useFileListWidth()
		const { fileId, openDetails, openFile } = useRouteParameters()

		return {
			fileId,
			fileListWidth,
			headers: useFileListHeaders(),
			openDetails,
			openFile,

			activeStore,
			selectionStore,
			userConfigStore,

			t,
		}
	},

	data() {
		return {
			FileEntry,
			FileEntryGrid,
			scrollToIndex: 0,
			openFileId: null as number|null,
		}
	},

	computed: {
		userConfig(): UserConfig {
			return this.userConfigStore.userConfig
		},

		isMtimeAvailable() {
			// Hide mtime column on narrow screens
			if (this.fileListWidth < 768) {
				return false
			}
			return this.nodes.some(node => node.mtime !== undefined)
		},
		isSizeAvailable() {
			// Hide size column on narrow screens
			if (this.fileListWidth < 768) {
				return false
			}
			return this.nodes.some(node => node.size !== undefined)
		},

		cantUpload() {
			return this.currentFolder && (this.currentFolder.permissions & Permission.CREATE) === 0
		},

		isQuotaExceeded() {
			return this.currentFolder?.attributes?.['quota-available-bytes'] === 0
		},

		caption() {
			const defaultCaption = t('files', 'List of files and folders.')
			const viewCaption = this.currentView.caption || defaultCaption
			const cantUploadCaption = this.cantUpload ? t('files', 'You do not have permission to upload or create files here.') : null
			const quotaExceededCaption = this.isQuotaExceeded ? t('files', 'You have used your space quota and cannot upload files anymore.') : null
			const sortableCaption = t('files', 'Column headers with buttons are sortable.')
			const virtualListNote = t('files', 'This list is not fully rendered for performance reasons. The files will be rendered as you navigate through the list.')
			return [
				viewCaption,
				cantUploadCaption,
				quotaExceededCaption,
				sortableCaption,
				virtualListNote,
			].filter(Boolean).join('\n')
		},

		selectedNodes() {
			return this.selectionStore.selected
		},

		isNoneSelected() {
			return this.selectedNodes.length === 0
		},
	},

	watch: {
		fileId: {
			handler(fileId) {
				this.scrollToFile(fileId, false)
			},
			immediate: true,
		},

		openFile: {
			handler(openFile) {
				if (!openFile || !this.fileId) {
					return
				}

				this.handleOpenFile(this.fileId)
			},
			immediate: true,
		},

		openDetails: {
			handler(openDetails) {
				// wait for scrolling and updating the actions to settle
				this.$nextTick(() => {
					if (!openDetails || !this.fileId) {
						return
					}

					this.openSidebarForFile(this.fileId)
				})
			},
			immediate: true,
		},
	},

	created() {
		useHotKey('Escape', this.unselectFile, {
			stop: true,
			prevent: true,
		})

		useHotKey(['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'], this.onKeyDown, {
			stop: true,
			prevent: true,
		})
	},

	mounted() {
		// Add events on parent to cover both the table and DragAndDrop notice
		const mainContent = window.document.querySelector('main.app-content') as HTMLElement
		mainContent.addEventListener('dragover', this.onDragOver)
		subscribe('files:sidebar:closed', this.onSidebarClosed)
	},

	beforeDestroy() {
		const mainContent = window.document.querySelector('main.app-content') as HTMLElement
		mainContent.removeEventListener('dragover', this.onDragOver)
		unsubscribe('files:sidebar:closed', this.onSidebarClosed)
	},

	methods: {
		openSidebarForFile(fileId) {
			// Open the sidebar for the given URL fileid
			// iif we just loaded the app.
			const node = this.nodes.find(n => n.fileid === fileId) as NcNode
			if (node && sidebarAction?.enabled?.([node], this.currentView)) {
				logger.debug('Opening sidebar on file ' + node.path, { node })
				sidebarAction.exec(node, this.currentView, this.currentFolder.path)
				return
			}
			logger.error(`Failed to open sidebar on file ${fileId}, file isn't cached yet !`, { fileId, node })
		},

		scrollToFile(fileId: number|null, warn = true) {
			if (fileId) {
				// Do not uselessly scroll to the top of the list.
				if (fileId === this.currentFolder.fileid) {
					return
				}

				const index = this.nodes.findIndex(node => node.fileid === fileId)
				if (warn && index === -1 && fileId !== this.currentFolder.fileid) {
					showError(t('files', 'File not found'))
				}

				this.scrollToIndex = Math.max(0, index)
			}
		},

		/**
		 * Unselect the current file and clear open parameters from the URL
		 */
		unselectFile() {
			const query = { ...this.$route.query }
			delete query.openfile
			delete query.opendetails

			this.activeStore.clearActiveNode()
			window.OCP.Files.Router.goToRoute(
				null,
				{ ...this.$route.params, fileid: String(this.currentFolder.fileid ?? '') },
				query,
				true,
			)
		},

		// When sidebar is closed, we remove the openDetails parameter from the URL
		onSidebarClosed() {
			if (this.openDetails) {
				const query = { ...this.$route.query }
				delete query.opendetails
				window.OCP.Files.Router.goToRoute(
					null,
					this.$route.params,
					query,
				)
			}
		},

		/**
		 * Handle opening a file (e.g. by ?openfile=true)
		 * @param fileId File to open
		 */
		async handleOpenFile(fileId: number) {
			const node = this.nodes.find(n => n.fileid === fileId) as NcNode
			if (node === undefined) {
				return
			}

			if (node.type === FileType.File) {
				const defaultAction = getFileActions()
					// Get only default actions (visible and hidden)
					.filter((action) => !!action?.default)
					// Find actions that are either always enabled or enabled for the current node
					.filter((action) => !action.enabled || action.enabled([node], this.currentView))
					.filter((action) => action.id !== 'download')
					// Sort enabled default actions by order
					.sort((a, b) => (a.order || 0) - (b.order || 0))
					// Get the first one
					.at(0)

				// Some file types do not have a default action (e.g. they can only be downloaded)
				// So if there is an enabled default action, so execute it
				if (defaultAction) {
					logger.debug('Opening file ' + node.path, { node })
					return await defaultAction.exec(node, this.currentView, this.currentFolder.path)
				}
			}
			// The file is either a folder or has no default action other than downloading
			// in this case we need to open the details instead and remove the route from the history
			const query = this.$route.query
			delete query.openfile
			query.opendetails = ''

			logger.debug('Ignore `openfile` query and replacing with `opendetails` for ' + node.path, { node })
			await this.$router.replace({
				...(this.$route as Location),
				query,
			})
		},

		onDragOver(event: DragEvent) {
			// Detect if we're only dragging existing files or not
			const isForeignFile = event.dataTransfer?.types.includes('Files')
			if (isForeignFile) {
				// Only handle uploading of existing Nextcloud files
				// See DragAndDropNotice for handling of foreign files
				return
			}

			event.preventDefault()
			event.stopPropagation()

			const tableElement = (this.$refs.table as ComponentPublicInstance<typeof VirtualList>).$el
			const tableTop = tableElement.getBoundingClientRect().top
			const tableBottom = tableTop + tableElement.getBoundingClientRect().height

			// If reaching top, scroll up. Using 100 because of the floating header
			if (event.clientY < tableTop + 100) {
				tableElement.scrollTop = tableElement.scrollTop - 25
				return
			}

			// If reaching bottom, scroll down
			if (event.clientY > tableBottom - 50) {
				tableElement.scrollTop = tableElement.scrollTop + 25
			}
		},

		onKeyDown(event: KeyboardEvent) {
			// Up and down arrow keys
			if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
				const columnCount = this.$refs.table?.columnCount ?? 1
				const index = this.nodes.findIndex(node => node.fileid === this.fileId) ?? 0
				const nextIndex = event.key === 'ArrowUp' ? index - columnCount : index + columnCount
				if (nextIndex < 0 || nextIndex >= this.nodes.length) {
					return
				}

				const nextNode = this.nodes[nextIndex]

				if (nextNode && nextNode?.fileid) {
					this.setActiveNode(nextNode)
				}
			}

			// if grid mode, left and right arrow keys
			if (this.userConfig.grid_view && (event.key === 'ArrowLeft' || event.key === 'ArrowRight')) {
				const index = this.nodes.findIndex(node => node.fileid === this.fileId) ?? 0
				const nextIndex = event.key === 'ArrowLeft' ? index - 1 : index + 1
				if (nextIndex < 0 || nextIndex >= this.nodes.length) {
					return
				}

				const nextNode = this.nodes[nextIndex]

				if (nextNode && nextNode?.fileid) {
					this.setActiveNode(nextNode)
				}
			}
		},

		setActiveNode(node: NcNode & { fileid: number }) {
			logger.debug('Navigating to file ' + node.path, { node, fileid: node.fileid })
			this.scrollToFile(node.fileid)

			// Remove openfile and opendetails from the URL
			const query = { ...this.$route.query }
			delete query.openfile
			delete query.opendetails

			this.activeStore.setActiveNode(node)

			// Silent update of the URL
			window.OCP.Files.Router.goToRoute(
				null,
				{ ...this.$route.params, fileid: String(node.fileid) },
				query,
				true,
			)
		},
	},
})
</script>

<style scoped lang="scss">
.files-list {
	--row-height: 55px;
	--cell-margin: 14px;

	--checkbox-padding: calc((var(--row-height) - var(--checkbox-size)) / 2);
	--checkbox-size: 24px;
	--clickable-area: var(--default-clickable-area);
	--icon-preview-size: 32px;

	--fixed-block-start-position: var(--default-clickable-area);
	overflow: auto;
	height: 100%;
	will-change: scroll-position;

	&:has(.file-list-filters__active) {
		--fixed-block-start-position: calc(var(--default-clickable-area) + var(--default-grid-baseline) + var(--clickable-area-small));
	}

	& :deep() {
		// Table head, body and footer
		tbody {
			will-change: padding;
			contain: layout paint style;
			display: flex;
			flex-direction: column;
			width: 100%;
			// Necessary for virtual scrolling absolute
			position: relative;

			/* Hover effect on tbody lines only */
			tr {
				contain: strict;
				&:hover,
				&:focus {
					background-color: var(--color-background-dark);
				}
			}
		}

		// Before table and thead
		.files-list__before {
			display: flex;
			flex-direction: column;
		}

		.files-list__selected {
			padding-inline-end: 12px;
			white-space: nowrap;
		}

		.files-list__table {
			display: block;

			&.files-list__table--with-thead-overlay {
				// Hide the table header below the overlay
				margin-block-start: calc(-1 * var(--row-height));
			}
		}

		.files-list__filters {
			// Pinned on top when scrolling above table header
			position: sticky;
			top: 0;
			// ensure there is a background to hide the file list on scroll
			background-color: var(--color-main-background);
			z-index: 10;
			// fixed the size
			padding-inline: var(--row-height) var(--default-grid-baseline, 4px);
			height: var(--fixed-block-start-position);
			width: 100%;
		}

		.files-list__thead-overlay {
			// Pinned on top when scrolling
			position: sticky;
			top: var(--fixed-block-start-position);
			// Save space for a row checkbox
			margin-inline-start: var(--row-height);
			// More than .files-list__thead
			z-index: 20;

			display: flex;
			align-items: center;

			// Reuse row styles
			background-color: var(--color-main-background);
			border-block-end: 1px solid var(--color-border);
			height: var(--row-height);
		}

		.files-list__thead,
		.files-list__tfoot {
			display: flex;
			flex-direction: column;
			width: 100%;
			background-color: var(--color-main-background);
		}

		// Table header
		.files-list__thead {
			// Pinned on top when scrolling
			position: sticky;
			z-index: 10;
			top: var(--fixed-block-start-position);
		}

		tr {
			position: relative;
			display: flex;
			align-items: center;
			width: 100%;
			border-block-end: 1px solid var(--color-border);
			box-sizing: border-box;
			user-select: none;
			height: var(--row-height);
		}

		td, th {
			display: flex;
			align-items: center;
			flex: 0 0 auto;
			justify-content: start;
			width: var(--row-height);
			height: var(--row-height);
			margin: 0;
			padding: 0;
			color: var(--color-text-maxcontrast);
			border: none;

			// Columns should try to add any text
			// node wrapped in a span. That should help
			// with the ellipsis on overflow.
			span {
				overflow: hidden;
				white-space: nowrap;
				text-overflow: ellipsis;
			}
		}

		.files-list__row--failed {
			position: absolute;
			display: block;
			top: 0;
			inset-inline: 0;
			bottom: 0;
			opacity: .1;
			z-index: -1;
			background: var(--color-error);
		}

		.files-list__row-checkbox {
			justify-content: center;

			.checkbox-radio-switch {
				display: flex;
				justify-content: center;

				--icon-size: var(--checkbox-size);

				label.checkbox-radio-switch__label {
					width: var(--clickable-area);
					height: var(--clickable-area);
					margin: 0;
					padding: calc((var(--clickable-area) - var(--checkbox-size)) / 2);
				}

				.checkbox-radio-switch__icon {
					margin: 0 !important;
				}
			}
		}

		.files-list__row {
			&:hover, &:focus, &:active, &--active, &--dragover {
				// WCAG AA compliant
				background-color: var(--color-background-hover);
				// text-maxcontrast have been designed to pass WCAG AA over
				// a white background, we need to adjust then.
				--color-text-maxcontrast: var(--color-main-text);
				> * {
					--color-border: var(--color-border-dark);
				}

				// Hover state of the row should also change the favorite markers background
				.favorite-marker-icon svg path {
					stroke: var(--color-background-hover);
				}
			}

			&--dragover * {
				// Prevent dropping on row children
				pointer-events: none;
			}
		}

		// Entry preview or mime icon
		.files-list__row-icon {
			position: relative;
			display: flex;
			overflow: visible;
			align-items: center;
			// No shrinking or growing allowed
			flex: 0 0 var(--icon-preview-size);
			justify-content: center;
			width: var(--icon-preview-size);
			height: 100%;
			// Show same padding as the checkbox right padding for visual balance
			margin-inline-end: var(--checkbox-padding);
			color: var(--color-primary-element);

			// Icon is also clickable
			* {
				cursor: pointer;
			}

			& > span {
				justify-content: flex-start;

				&:not(.files-list__row-icon-favorite) svg {
					width: var(--icon-preview-size);
					height: var(--icon-preview-size);
				}

				// Slightly increase the size of the folder icon
				&.folder-icon,
				&.folder-open-icon {
					margin: -3px;
					svg {
						width: calc(var(--icon-preview-size) + 6px);
						height: calc(var(--icon-preview-size) + 6px);
					}
				}
			}

			&-preview-container {
				position: relative; // Needed for the blurshash to be positioned correctly
				overflow: hidden;
				width: var(--icon-preview-size);
				height: var(--icon-preview-size);
				border-radius: var(--border-radius);
			}

			&-blurhash {
				position: absolute;
				inset-block-start: 0;
				inset-inline-start: 0;
				height: 100%;
				width: 100%;
				object-fit: cover;
			}

			&-preview {
				// Center and contain the preview
				object-fit: contain;
				object-position: center;

				height: 100%;
				width: 100%;

				/* Preview not loaded animation effect */
				&:not(.files-list__row-icon-preview--loaded) {
					background: var(--color-loading-dark);
					// animation: preview-gradient-fade 1.2s ease-in-out infinite;
				}
			}

			&-favorite {
				position: absolute;
				top: 0px;
				inset-inline-end: -10px;
			}

			// File and folder overlay
			&-overlay {
				position: absolute;
				max-height: calc(var(--icon-preview-size) * 0.5);
				max-width: calc(var(--icon-preview-size) * 0.5);
				color: var(--color-primary-element-text);
				// better alignment with the folder icon
				margin-block-start: 2px;

				// Improve icon contrast with a background for files
				&--file {
					color: var(--color-main-text);
					background: var(--color-main-background);
					border-radius: 100%;
				}
			}
		}

		// Entry link
		.files-list__row-name {
			// Prevent link from overflowing
			overflow: hidden;
			// Take as much space as possible
			flex: 1 1 auto;

			button.files-list__row-name-link {
				display: flex;
				align-items: center;
				text-align: start;
				// Fill cell height and width
				width: 100%;
				height: 100%;
				// Necessary for flex grow to work
				min-width: 0;
				margin: 0;
				padding: 0;

				// Already added to the inner text, see rule below
				&:focus-visible {
					outline: none !important;
				}

				// Keyboard indicator a11y
				&:focus .files-list__row-name-text {
					outline: var(--border-width-input-focused) solid var(--color-main-text) !important;
					border-radius: var(--border-radius-element);
				}
				&:focus:not(:focus-visible) .files-list__row-name-text {
					outline: none !important;
				}
			}

			.files-list__row-name-text {
				color: var(--color-main-text);
				// Make some space for the outline
				padding: var(--default-grid-baseline) calc(2 * var(--default-grid-baseline));
				padding-inline-start: -10px;
				// Align two name and ext
				display: inline-flex;
			}

			.files-list__row-name-ext {
				color: var(--color-text-maxcontrast);
				// always show the extension
				overflow: visible;
			}
		}

		// Rename form
		.files-list__row-rename {
			width: 100%;
			max-width: 600px;
			input {
				width: 100%;
				// Align with text, 0 - padding - border
				margin-inline-start: -8px;
				padding: 2px 6px;
				border-width: 2px;

				&:invalid {
					// Show red border on invalid input
					border-color: var(--color-error);
					color: red;
				}
			}
		}

		.files-list__row-actions {
			// take as much space as necessary
			width: auto;

			// Add margin to all cells after the actions
			& ~ td,
			& ~ th {
				margin: 0 var(--cell-margin);
			}

			button {
				.button-vue__text {
					// Remove bold from default button styling
					font-weight: normal;
				}
			}
		}

		.files-list__row-action--inline {
			margin-inline-end: 7px;
		}

		.files-list__row-mtime,
		.files-list__row-size {
			color: var(--color-text-maxcontrast);
		}
		.files-list__row-size {
			width: calc(var(--row-height) * 1.5);
			// Right align content/text
			justify-content: flex-end;
		}

		.files-list__row-mtime {
			width: calc(var(--row-height) * 2);
		}

		.files-list__row-column-custom {
			width: calc(var(--row-height) * 2);
		}
	}
}

@media screen and (max-width: 512px) {
	.files-list :deep(.files-list__filters) {
		// Reduce padding on mobile
		padding-inline: var(--default-grid-baseline, 4px);
	}
}

</style>

<style lang="scss">
// Grid mode
.files-list--grid tbody.files-list__tbody {
	--item-padding: 16px;
	--icon-preview-size: 166px;
	--name-height: var(--default-clickable-area);
	--mtime-height: calc(var(--font-size-small) + var(--default-grid-baseline));
	--row-width: calc(var(--icon-preview-size) + var(--item-padding) * 2);
	--row-height: calc(var(--icon-preview-size) + var(--name-height) + var(--mtime-height) + var(--item-padding) * 2);
	--checkbox-padding: 0px;
	display: grid;
	grid-template-columns: repeat(auto-fill, var(--row-width));

	align-content: center;
	align-items: center;
	justify-content: space-around;
	justify-items: center;

	tr {
		display: flex;
		flex-direction: column;
		width: var(--row-width);
		height: var(--row-height);
		border: none;
		border-radius: var(--border-radius-large);
		padding: var(--item-padding);
	}

	// Checkbox in the top left
	.files-list__row-checkbox {
		position: absolute;
		z-index: 9;
		top: calc(var(--item-padding) / 2);
		inset-inline-start: calc(var(--item-padding) / 2);
		overflow: hidden;
		--checkbox-container-size: 44px;
		width: var(--checkbox-container-size);
		height: var(--checkbox-container-size);

		// Add a background to the checkbox so we do not see the image through it.
		.checkbox-radio-switch__content::after {
			content: '';
			width: 16px;
			height: 16px;
			position: absolute;
			inset-inline-start: 50%;
			margin-inline-start: -8px;
			z-index: -1;
			background: var(--color-main-background);
		}
	}

	// Star icon in the top right
	.files-list__row-icon-favorite {
		position: absolute;
		top: 0;
		inset-inline-end: 0;
		display: flex;
		align-items: center;
		justify-content: center;
		width: var(--clickable-area);
		height: var(--clickable-area);
	}

	.files-list__row-name {
		display: flex;
		flex-direction: column;
		width: var(--icon-preview-size);
		height: calc(var(--icon-preview-size) + var(--name-height));
		// Ensure that the name outline is visible.
		overflow: visible;

		span.files-list__row-icon {
			width: var(--icon-preview-size);
			height: var(--icon-preview-size);
		}

		.files-list__row-name-text {
			margin: 0;
			// Ensure that the outline is not too close to the text.
			margin-inline-start: -4px;
			padding: 0px 4px;
		}
	}

	.files-list__row-mtime {
		width: var(--icon-preview-size);
		height: var(--mtime-height);
		font-size: var(--font-size-small);
	}

	.files-list__row-actions {
		position: absolute;
		inset-inline-end: calc(var(--clickable-area) / 4);
		inset-block-end: calc(var(--mtime-height) / 2);
		width: var(--clickable-area);
		height: var(--clickable-area);
	}
}

@media screen and (max-width: 768px) {
	// there is no mtime
	.files-list--grid tbody.files-list__tbody {
		--mtime-height: 0px;

		// so we move the action to the name
		.files-list__row-actions {
			inset-block-end: var(--item-padding);
		}

		// and we need to keep space on the name for the actions
		.files-list__row-name-text {
			padding-inline-end: var(--clickable-area) !important;
		}
	}
}
</style>
