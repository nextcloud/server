<!--
  - @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<Fragment>
		<!-- Drag and drop notice -->
		<DragAndDropNotice v-if="canUpload && filesListWidth >= 512"
			:current-folder="currentFolder"
			:dragover.sync="dragover"
			:style="{ height: dndNoticeHeight }" />

		<VirtualList ref="table"
			:data-component="userConfig.grid_view ? FileEntryGrid : FileEntry"
			:data-key="'source'"
			:data-sources="nodes"
			:grid-mode="userConfig.grid_view"
			:extra-props="{
				isMtimeAvailable,
				isSizeAvailable,
				nodes,
				filesListWidth,
			}"
			:scroll-to-index="scrollToIndex"
			@scroll="onScroll">
			<!-- Accessibility description and headers -->
			<template #before>
				<!-- Accessibility description -->
				<caption class="hidden-visually">
					{{ currentView.caption || t('files', 'List of files and folders.') }}
					{{ t('files', 'This list is not fully rendered for performance reasons. The files will be rendered as you navigate through the list.') }}
				</caption>

				<!-- Headers -->
				<FilesListHeader v-for="header in sortedHeaders"
					:key="header.id"
					:current-folder="currentFolder"
					:current-view="currentView"
					:header="header" />
			</template>

			<!-- Thead-->
			<template #header>
				<!-- Table header and sort buttons -->
				<FilesListTableHeader ref="thead"
					:files-list-width="filesListWidth"
					:is-mtime-available="isMtimeAvailable"
					:is-size-available="isSizeAvailable"
					:nodes="nodes" />
			</template>

			<!-- Tfoot-->
			<template #footer>
				<FilesListTableFooter :files-list-width="filesListWidth"
					:is-mtime-available="isMtimeAvailable"
					:is-size-available="isSizeAvailable"
					:nodes="nodes"
					:summary="summary" />
			</template>
		</VirtualList>
	</Fragment>
</template>

<script lang="ts">
import type { Node as NcNode } from '@nextcloud/files'
import type { PropType } from 'vue'
import type { UserConfig } from '../types.ts'

import { Fragment } from 'vue-frag'
import { getFileListHeaders, Folder, View, Permission } from '@nextcloud/files'
import { showError } from '@nextcloud/dialogs'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import Vue from 'vue'

import { action as sidebarAction } from '../actions/sidebarAction.ts'
import { useUserConfigStore } from '../store/userconfig.ts'
import DragAndDropNotice from './DragAndDropNotice.vue'
import FileEntry from './FileEntry.vue'
import FileEntryGrid from './FileEntryGrid.vue'
import FilesListHeader from './FilesListHeader.vue'
import FilesListTableFooter from './FilesListTableFooter.vue'
import FilesListTableHeader from './FilesListTableHeader.vue'
import filesListWidthMixin from '../mixins/filesListWidth.ts'
import logger from '../logger.js'
import VirtualList from './VirtualList.vue'

export default Vue.extend({
	name: 'FilesListVirtual',

	components: {
		DragAndDropNotice,
		FilesListHeader,
		FilesListTableFooter,
		FilesListTableHeader,
		Fragment,
		VirtualList,
	},

	mixins: [
		filesListWidthMixin,
	],

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
	},

	setup() {
		const userConfigStore = useUserConfigStore()
		return {
			userConfigStore,
		}
	},

	data() {
		return {
			FileEntry,
			FileEntryGrid,
			headers: getFileListHeaders(),
			scrollToIndex: 0,
			dragover: false,
			dndNoticeHeight: 0,
		}
	},

	computed: {
		userConfig(): UserConfig {
			return this.userConfigStore.userConfig
		},

		files() {
			return this.nodes.filter(node => node.type === 'file')
		},

		fileId() {
			return parseInt(this.$route.params.fileid) || null
		},

		summaryFile() {
			const count = this.files.length
			return n('files', '{count} file', '{count} files', count, { count })
		},
		summaryFolder() {
			const count = this.nodes.length - this.files.length
			return n('files', '{count} folder', '{count} folders', count, { count })
		},
		summary() {
			return t('files', '{summaryFile} and {summaryFolder}', this)
		},
		isMtimeAvailable() {
			// Hide mtime column on narrow screens
			if (this.filesListWidth < 768) {
				return false
			}
			return this.nodes.some(node => node.mtime !== undefined)
		},
		isSizeAvailable() {
			// Hide size column on narrow screens
			if (this.filesListWidth < 768) {
				return false
			}
			return this.nodes.some(node => node.attributes.size !== undefined)
		},

		sortedHeaders() {
			if (!this.currentFolder || !this.currentView) {
				return []
			}

			return [...this.headers].sort((a, b) => a.order - b.order)
		},

		canUpload() {
			return this.currentFolder && (this.currentFolder.permissions & Permission.CREATE) !== 0
		},
	},

	watch: {
		fileId(fileId) {
			this.scrollToFile(fileId, false)
		},
	},

	mounted() {
		// Add events on parent to cover both the table and DragAndDrop notice
		const mainContent = window.document.querySelector('main.app-content') as HTMLElement
		mainContent.addEventListener('dragover', this.onDragOver)
		mainContent.addEventListener('dragleave', this.onDragLeave)

		this.scrollToFile(this.fileId)
		this.openSidebarForFile(this.fileId)
	},

	methods: {
		// Open the file sidebar if we have the room for it
		// but don't open the sidebar for the current folder
		openSidebarForFile(fileId) {
			if (document.documentElement.clientWidth > 1024 && this.currentFolder.fileid !== fileId) {
				// Open the sidebar for the given URL fileid
				// iif we just loaded the app.
				const node = this.nodes.find(n => n.fileid === fileId) as NcNode
				if (node && sidebarAction?.enabled?.([node], this.currentView)) {
					logger.debug('Opening sidebar on file ' + node.path, { node })
					sidebarAction.exec(node, this.currentView, this.currentFolder.path)
				}
			}
		},

		scrollToFile(fileId: number, warn = true) {
			if (fileId) {
				const index = this.nodes.findIndex(node => node.fileid === fileId)
				if (warn && index === -1 && fileId !== this.currentFolder.fileid) {
					showError(this.t('files', 'File not found'))
				}
				this.scrollToIndex = Math.max(0, index)
			}
		},

		getFileId(node) {
			return node.fileid
		},

		onDragOver(event: DragEvent) {
			// Detect if we're only dragging existing files or not
			const isForeignFile = event.dataTransfer?.types.includes('Files')
			if (isForeignFile) {
				this.dragover = true
			} else {
				this.dragover = false
			}

			event.preventDefault()
			event.stopPropagation()

			// If reaching top, scroll up
			const firstVisible = this.$refs.table?.$el?.querySelector('.files-list__row--visible') as HTMLElement
			const firstSibling = firstVisible?.previousElementSibling as HTMLElement
			if ([firstVisible, firstSibling].some(elmt => elmt?.contains(event.target as Node))) {
				this.$refs.table.$el.scrollTop = this.$refs.table.$el.scrollTop - 25
				return
			}

			// If reaching bottom, scroll down
			const lastVisible = [...(this.$refs.table?.$el?.querySelectorAll('.files-list__row--visible') || [])].pop() as HTMLElement
			const nextSibling = lastVisible?.nextElementSibling as HTMLElement
			if ([lastVisible, nextSibling].some(elmt => elmt?.contains(event.target as Node))) {
				this.$refs.table.$el.scrollTop = this.$refs.table.$el.scrollTop + 25
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

		onScroll() {
			// Update the sticky position of the thead to adapt to the scroll
			this.dndNoticeHeight = (this.$refs.thead.$el?.getBoundingClientRect?.()?.top ?? 0) + 'px'
		},

		t,
	},
})
</script>

<style scoped lang="scss">
.files-list {
	--row-height: 55px;
	--cell-margin: 14px;

	--checkbox-padding: calc((var(--row-height) - var(--checkbox-size)) / 2);
	--checkbox-size: 24px;
	--clickable-area: 44px;
	--icon-preview-size: 32px;

	display: block;
	overflow: auto;
	height: 100%;

	&::v-deep {
		// Table head, body and footer
		tbody {
			will-change: scroll-position, padding;
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
			contain: strict;
			display: flex;
			flex-direction: column;
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
			top: 0;
		}

		// Table footer
		.files-list__tfoot {
			min-height: 300px;
		}

		tr {
			position: relative;
			display: flex;
			align-items: center;
			width: 100%;
			user-select: none;
			border-bottom: 1px solid var(--color-border);
			user-select: none;
			height: var(--row-height);
		}

		td, th {
			display: flex;
			align-items: center;
			flex: 0 0 auto;
			justify-content: left;
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
			left: 0;
			right: 0;
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
					stroke: var(--color-background-dark);
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
			margin-right: var(--checkbox-padding);
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

			&-preview {
				overflow: hidden;
				width: var(--icon-preview-size);
				height: var(--icon-preview-size);
				border-radius: var(--border-radius);
				// Center and contain the preview
				object-fit: contain;
				object-position: center;

				/* Preview not loaded animation effect */
				&:not(.files-list__row-icon-preview--loaded) {
					background: var(--color-loading-dark);
					// animation: preview-gradient-fade 1.2s ease-in-out infinite;
				}
			}

			&-favorite {
				position: absolute;
				top: 0px;
				right: -10px;
			}

			// Folder overlay
			&-overlay {
				position: absolute;
				max-height: calc(var(--icon-preview-size) * 0.5);
				max-width: calc(var(--icon-preview-size) * 0.5);
				color: var(--color-main-background);
				// better alignment with the folder icon
				margin-top: 2px;
			}
		}

		// Entry link
		.files-list__row-name {
			// Prevent link from overflowing
			overflow: hidden;
			// Take as much space as possible
			flex: 1 1 auto;

			a {
				display: flex;
				align-items: center;
				// Fill cell height and width
				width: 100%;
				height: 100%;
				// Necessary for flex grow to work
				min-width: 0;

				// Already added to the inner text, see rule below
				&:focus-visible {
					outline: none;
				}

				// Keyboard indicator a11y
				&:focus .files-list__row-name-text,
				&:focus-visible .files-list__row-name-text {
					outline: 2px solid var(--color-main-text) !important;
					border-radius: 20px;
				}
			}

			.files-list__row-name-text {
				color: var(--color-main-text);
				// Make some space for the outline
				padding: 5px 10px;
				margin-left: -10px;
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
				margin-left: -8px;
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
			margin-right: 7px;
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
</style>

<style lang="scss">
// Grid mode
tbody.files-list__tbody.files-list__tbody--grid {
	--half-clickable-area: calc(var(--clickable-area) / 2);
	--row-width: 160px;
	// We use half of the clickable area as visual balance margin
	--row-height: calc(var(--row-width) - var(--half-clickable-area));
	--icon-preview-size: calc(var(--row-width) - var(--clickable-area));
	--checkbox-padding: 0px;

	display: grid;
	grid-template-columns: repeat(auto-fill, var(--row-width));
	grid-gap: 15px;
	row-gap: 15px;

	align-content: center;
	align-items: center;
	justify-content: space-around;
	justify-items: center;

	tr {
		width: var(--row-width);
		height: calc(var(--row-height) + var(--clickable-area));
		border: none;
		border-radius: var(--border-radius);
	}

	// Checkbox in the top left
	.files-list__row-checkbox {
		position: absolute;
		z-index: 9;
		top: 0;
		left: 0;
		overflow: hidden;
		width: var(--clickable-area);
		height: var(--clickable-area);
		border-radius: var(--half-clickable-area);
	}

	// Star icon in the top right
	.files-list__row-icon-favorite {
		position: absolute;
		top: 0;
		right: 0;
		display: flex;
		align-items: center;
		justify-content: center;
		width: var(--clickable-area);
		height: var(--clickable-area);
	}

	.files-list__row-name {
		display: grid;
		justify-content: stretch;
		width: 100%;
		height: 100%;
		grid-auto-rows: var(--row-height) var(--clickable-area);

		span.files-list__row-icon {
			width: 100%;
			height: 100%;
			// Visual balance, we use half of the clickable area
			// as a margin around the preview
			padding-top: var(--half-clickable-area);
		}

		a.files-list__row-name-link {
			// Minus action menu
			width: calc(100% - var(--clickable-area));
			height: var(--clickable-area);
		}

		.files-list__row-name-text {
			margin: 0;
			padding-right: 0;
		}
	}

	.files-list__row-actions {
		position: absolute;
		right: 0;
		bottom: 0;
		width: var(--clickable-area);
		height: var(--clickable-area);
	}
}
</style>
