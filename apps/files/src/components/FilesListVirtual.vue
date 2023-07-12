<!--
  - @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @license GNU AGPL version 3 or any later version
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
	<RecycleScroller ref="recycleScroller"
		class="files-list"
		key-field="source"
		:items="nodes"
		:item-size="55"
		:table-mode="true"
		item-class="files-list__row"
		item-tag="tr"
		list-class="files-list__body"
		list-tag="tbody"
		role="table">
		<template #default="{ item, active, index }">
			<!-- File row -->
			<FileEntry :active="active"
				:index="index"
				:is-size-available="isSizeAvailable"
				:files-list-width="filesListWidth"
				:nodes="nodes"
				:source="item" />
		</template>

		<template #before>
			<!-- Accessibility description -->
			<caption class="hidden-visually">
				{{ currentView.caption || '' }}
				{{ t('files', 'This list is not fully rendered for performances reasons. The files will be rendered as you navigate through the list.') }}
			</caption>

			<!-- Thead-->
			<FilesListHeader :files-list-width="filesListWidth"
				:is-size-available="isSizeAvailable"
				:nodes="nodes" />
		</template>

		<template #after>
			<!-- Tfoot-->
			<FilesListFooter :files-list-width="filesListWidth"
				:is-size-available="isSizeAvailable"
				:nodes="nodes"
				:summary="summary" />
		</template>
	</RecycleScroller>
</template>

<script lang="ts">
import { RecycleScroller } from 'vue-virtual-scroller'
import { translate, translatePlural } from '@nextcloud/l10n'
import Vue from 'vue'

import FileEntry from './FileEntry.vue'
import FilesListFooter from './FilesListFooter.vue'
import FilesListHeader from './FilesListHeader.vue'
import filesListWidthMixin from '../mixins/filesListWidth.ts'

export default Vue.extend({
	name: 'FilesListVirtual',

	components: {
		RecycleScroller,
		FileEntry,
		FilesListHeader,
		FilesListFooter,
	},

	mixins: [
		filesListWidthMixin,
	],

	props: {
		currentView: {
			type: Object,
			required: true,
		},
		nodes: {
			type: Array,
			required: true,
		},
	},

	data() {
		return {
			FileEntry,
		}
	},

	computed: {
		files() {
			return this.nodes.filter(node => node.type === 'file')
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
		isSizeAvailable() {
			// Hide size column on narrow screens
			if (this.filesListWidth < 768) {
				return false
			}
			return this.nodes.some(node => node.attributes.size !== undefined)
		},
	},

	mounted() {
		// Make the root recycle scroller a table for proper semantics
		const slots = this.$el.querySelectorAll('.vue-recycle-scroller__slot')
		slots[0].setAttribute('role', 'thead')
		slots[1].setAttribute('role', 'tfoot')
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
		tbody, .vue-recycle-scroller__slot {
			display: flex;
			flex-direction: column;
			width: 100%;
			// Necessary for virtual scrolling absolute
			position: relative;
		}

		// Table header
		.vue-recycle-scroller__slot[role='thead'] {
			// Pinned on top when scrolling
			position: sticky;
			z-index: 10;
			top: 0;
			height: var(--row-height);
			background-color: var(--color-main-background);
		}

		tr {
			position: absolute;
			display: flex;
			align-items: center;
			width: 100%;
			border-bottom: 1px solid var(--color-border);
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

			& > span {
				justify-content: flex-start;
			}

			&> span:not(.files-list__row-icon-favorite) svg {
				width: var(--icon-preview-size);
				height: var(--icon-preview-size);
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
				top: 4px;
				right: -8px;
				color: #ffcc00;
			}
		}

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

				// Keyboard indicator a11y
				&:focus .files-list__row-name-text,
				&:focus-visible .files-list__row-name-text {
					outline: 2px solid var(--color-main-text) !important;
					border-radius: 20px;
				}
			}

			.files-list__row-name-text {
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

		.files-list__row-column-custom {
			width: calc(var(--row-height) * 2);
		}
	}
}
</style>
