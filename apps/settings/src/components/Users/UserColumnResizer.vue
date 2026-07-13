<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<span
		class="column-resizer"
		role="separator"
		aria-orientation="vertical"
		:aria-label="t('settings', 'Drag or use the arrow keys to resize the {label} column. Double click to reset its width.', { label })"
		tabindex="0"
		:data-cy-column-resizer="column"
		@pointerdown.prevent="onPointerDown"
		@pointermove="onPointerMove"
		@pointerup="onPointerUp"
		@pointercancel="onPointerUp"
		@keydown="onKeydown"
		@dblclick="$emit('reset')" />
</template>

<script lang="ts">
import { translate as t } from '@nextcloud/l10n'
import Vue from 'vue'
import { clampColumnWidth, COLUMN_RESIZE_STEP } from '../../utils/userListColumns.ts'

export default Vue.extend({
	name: 'UserColumnResizer',

	props: {
		/** Column key, used as CSS variable suffix */
		column: {
			type: String,
			required: true,
		},

		/** Human readable column label */
		label: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			dragging: false,
			startX: 0,
			startWidth: 0,
		}
	},

	methods: {
		t,

		cellWidth(): number {
			const cell = (this.$el as HTMLElement).closest('th')
			return cell?.getBoundingClientRect().width ?? 0
		},

		resizeDirection(): number {
			return document.dir === 'rtl' ? -1 : 1
		},

		onPointerDown(event: PointerEvent) {
			this.dragging = true
			this.startX = event.clientX
			this.startWidth = this.cellWidth()
			const handle = event.target as HTMLElement
			handle.setPointerCapture?.(event.pointerId)
		},

		onPointerMove(event: PointerEvent) {
			if (!this.dragging) {
				return
			}
			const delta = (event.clientX - this.startX) * this.resizeDirection()
			this.$emit('resize', clampColumnWidth(this.startWidth + delta))
		},

		onPointerUp() {
			if (!this.dragging) {
				return
			}
			this.dragging = false
			this.$emit('resize-end')
		},

		onKeydown(event: KeyboardEvent) {
			const direction = { ArrowLeft: -1, ArrowRight: 1 }[event.key]
			if (direction === undefined) {
				return
			}
			event.preventDefault()
			const step = direction * COLUMN_RESIZE_STEP * this.resizeDirection()
			this.$emit('resize', clampColumnWidth(this.cellWidth() + step))
			this.$emit('resize-end')
		},
	},
})
</script>

<style lang="scss" scoped>
.column-resizer {
	position: absolute;
	z-index: 2;
	inset-block: 0;
	inset-inline-end: 0;
	width: calc(var(--default-grid-baseline) * 2);
	cursor: col-resize;
	touch-action: none;

	&::after {
		content: '';
		position: absolute;
		inset-block: var(--default-grid-baseline);
		inset-inline-end: calc(var(--default-grid-baseline) / 2);
		width: 2px;
		border-radius: 1px;
		background-color: var(--color-primary-element);
		opacity: 0;
	}

	&:hover::after,
	&:focus-visible::after {
		opacity: 1;
	}
}
</style>
