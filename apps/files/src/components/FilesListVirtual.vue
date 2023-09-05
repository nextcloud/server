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
	<VirtualList :data-component="FileEntry"
		:data-key="'source'"
		:data-sources="nodes"
		:item-height="56"
		:extra-props="{
			isMtimeAvailable,
			isSizeAvailable,
			nodes,
			filesListWidth,
		}"
		:scroll-to-index="scrollToIndex">
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
			<FilesListTableHeader :files-list-width="filesListWidth"
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
</template>

<script lang="ts">
import type { PropType } from 'vue'
import type { Node } from '@nextcloud/files'

import { translate, translatePlural } from '@nextcloud/l10n'
import { getFileListHeaders, Folder, View } from '@nextcloud/files'
import { showError } from '@nextcloud/dialogs'
import Vue from 'vue'

import { action as sidebarAction } from '../actions/sidebarAction.ts'
import FileEntry from './FileEntry.vue'
import FilesListHeader from './FilesListHeader.vue'
import FilesListTableFooter from './FilesListTableFooter.vue'
import FilesListTableHeader from './FilesListTableHeader.vue'
import filesListWidthMixin from '../mixins/filesListWidth.ts'
import logger from '../logger.js'
import VirtualList from './VirtualList.vue'

export default Vue.extend({
	name: 'FilesListVirtual',

	components: {
		FilesListHeader,
		FilesListTableHeader,
		FilesListTableFooter,
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
			type: Array as PropType<Node[]>,
			required: true,
		},
	},

	data() {
		return {
			FileEntry,
			headers: getFileListHeaders(),
			scrollToIndex: 0,
		}
	},

	computed: {
		files() {
			return this.nodes.filter(node => node.type === 'file')
		},

		fileId() {
			return parseInt(this.$route.params.fileid || this.$route.query.fileid) || null
		},

		summaryFile() {
			const count = this.files.length
			return translatePlural('files', '{count} file', '{count} files', count, { count })
		},
		summaryFolder() {
			const count = this.nodes.length - this.files.length
			return translatePlural('files', '{count} folder', '{count} folders', count, { count })
		},
		summary() {
			return translate('files', '{summaryFile} and {summaryFolder}', this)
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
	},

	mounted() {
		// Scroll to the file if it's in the url
		if (this.fileId) {
			const index = this.nodes.findIndex(node => node.fileid === this.fileId)
			if (index === -1) {
				showError(this.t('files', 'File not found'))
			}
			this.scrollToIndex = Math.max(0, index)
		}

		// Open the file sidebar if we have the room for it
		if (document.documentElement.clientWidth > 1024) {
			// Open the sidebar on the file if it's in the url and
			// we're just loaded the app for the first time.
			const node = this.nodes.find(n => n.fileid === this.fileId) as Node
			if (node && sidebarAction?.enabled?.([node], this.currentView)) {
				logger.debug('Opening sidebar on file ' + node.path, { node })
				sidebarAction.exec(node, this.currentView, this.currentFolder.path)
			}
		}
	},

	methods: {
		getFileId(node) {
			return node.fileid
		},

		t: translate,
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
			display: flex;
			flex-direction: column;
			width: 100%;
			// Necessary for virtual scrolling absolute
			position: relative;
		}

		// Before table and thead
		.files-list__before {
			display: flex;
			flex-direction: column;
		}

		// Table header
		.files-list__thead {
			// Pinned on top when scrolling
			position: sticky;
			z-index: 10;
			top: 0;
		}

		.files-list__thead,
		.files-list__tfoot {
			display: flex;
			width: 100%;
			background-color: var(--color-main-background);

		}

		tr {
			position: relative;
			display: flex;
			align-items: center;
			width: 100%;
			user-select: none;
			border-bottom: 1px solid var(--color-border);
			user-select: none;
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

		.files-list__row{
			&:hover, &:focus, &:active, &--active {
				background-color: var(--color-background-dark);
				// Hover state of the row should also change the favorite markers background
				.favorite-marker-icon svg path {
					stroke: var(--color-background-dark);
				}
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
				&.folder-icon {
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
				background-repeat: no-repeat;
				// Center and contain the preview
				background-position: center;
				background-size: contain;
			}

			&-favorite {
				position: absolute;
				top: 0px;
				right: -10px;
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
				&:not(:hover, :focus, :active) .button-vue__wrapper {
					// Also apply color-text-maxcontrast to non-active button
					color: var(--color-text-maxcontrast);
				}
			}
		}

		.files-list__row-mtime,
		.files-list__row-size {
			// Right align text
			justify-content: flex-end;
			width: calc(var(--row-height) * 1.5);
			// opacity varies with the size
			color: var(--color-main-text);

			// Icon is before text since size is right aligned
			.files-list__column-sort-button {
				padding: 0 16px 0 4px !important;
				.button-vue__wrapper {
					flex-direction: row;
				}
			}
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
