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
	<div class="files-list-drag-image">
		<span class="files-list-drag-image__icon">
			<span ref="previewImg" />
			<FolderIcon v-if="isSingleFolder" />
			<FileMultipleIcon v-else />
		</span>
		<span class="files-list-drag-image__name">{{ name }}</span>
	</div>
</template>

<script lang="ts">
import { FileType, Node, formatFileSize } from '@nextcloud/files'
import Vue from 'vue'

import FileMultipleIcon from 'vue-material-design-icons/FileMultiple.vue'
import FolderIcon from 'vue-material-design-icons/Folder.vue'

import { getSummaryFor } from '../utils/fileUtils.ts'

export default Vue.extend({
	name: 'DragAndDropPreview',

	components: {
		FileMultipleIcon,
		FolderIcon,
	},

	data() {
		return {
			nodes: [] as Node[],
		}
	},

	computed: {
		isSingleNode() {
			return this.nodes.length === 1
		},
		isSingleFolder() {
			return this.isSingleNode
				&& this.nodes[0].type === FileType.Folder
		},

		name() {
			if (!this.size) {
				return this.summary
			}
			return `${this.summary} – ${this.size}`
		},
		size() {
			const totalSize = this.nodes.reduce((total, node) => total + node.size || 0, 0)
			const size = parseInt(totalSize, 10) || 0
			if (typeof size !== 'number' || size < 0) {
				return null
			}
			return formatFileSize(size, true)
		},
		summary(): string {
			if (this.isSingleNode) {
				const node = this.nodes[0]
				return node.attributes?.displayName || node.basename
			}

			return getSummaryFor(this.nodes)
		},
	},

	methods: {
		update(nodes: Node[]) {
			this.nodes = nodes
			this.$refs.previewImg.replaceChildren()

			// Clone icon node from the list
			nodes.slice(0, 3).forEach(node => {
				const preview = document.querySelector(`[data-cy-files-list-row-fileid="${node.fileid}"] .files-list__row-icon img`)
				if (preview) {
					const previewElmt = this.$refs.previewImg as HTMLElement
					previewElmt.appendChild(preview.parentNode.cloneNode(true))
				}
			})

			this.$nextTick(() => {
				this.$emit('loaded', this.$el)
			})
		},
	},
})
</script>

<style lang="scss">
$size: 32px;
$stack-shift: 6px;

.files-list-drag-image {
	position: absolute;
	top: -9999px;
	left: -9999px;
	display: flex;
	overflow: hidden;
	align-items: center;
	height: 44px;
	padding: 6px 12px;
	background: var(--color-main-background);

	&__icon,
	.files-list__row-icon {
		display: flex;
		overflow: hidden;
		align-items: center;
		justify-content: center;
		width: 32px;
		height: 32px;
		border-radius: var(--border-radius);
	}

	&__icon {
		overflow: visible;
		margin-right: 12px;

		img {
			max-width: 100%;
			max-height: 100%;
		}

		.material-design-icon {
			color: var(--color-text-maxcontrast);
			&.folder-icon {
				color: var(--color-primary-element);
			}
		}

		// Previews container
		> span {
			display: flex;

			// Stack effect if more than one element
			.files-list__row-icon + .files-list__row-icon {
				margin-top: $stack-shift;
				margin-left: $stack-shift - $size;
				& + .files-list__row-icon {
					margin-top: $stack-shift * 2;
				}
			}
			// If we have manually clone the preview,
			// let's hide any fallback icons
			&:not(:empty) + * {
				display: none;
			}
		}
	}

	&__name {
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
	}
}

</style>
