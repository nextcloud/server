<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
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
import type { Node } from '@nextcloud/files'

import { FileType, formatFileSize } from '@nextcloud/files'
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
				return node.attributes?.displayname || node.basename
			}

			return getSummaryFor(this.nodes)
		},
	},

	methods: {
		update(nodes: Node[]) {
			this.nodes = nodes
			this.$refs.previewImg.replaceChildren()

			// Clone icon node from the list
			nodes.slice(0, 3).forEach((node) => {
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
$size: 28px;
$stack-shift: 6px;

.files-list-drag-image {
	position: absolute;
	top: -9999px;
	inset-inline-start: -9999px;
	display: flex;
	overflow: hidden;
	align-items: center;
	height: $size + $stack-shift;
	padding: $stack-shift $stack-shift * 2;
	background: var(--color-main-background);

	&__icon,
	.files-list__row-icon-preview-container {
		display: flex;
		overflow: hidden;
		align-items: center;
		justify-content: center;
		width: $size - $stack-shift;
		height: $size - $stack-shift;;
		border-radius: var(--border-radius);
	}

	&__icon {
		overflow: visible;
		margin-inline-end: $stack-shift * 2;

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
			// Max 3 elements
			> .files-list__row-icon-preview-container + .files-list__row-icon-preview-container {
				margin-top: $stack-shift;
				margin-inline-start: $stack-shift * 2 - $size;
				& + .files-list__row-icon-preview-container {
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
