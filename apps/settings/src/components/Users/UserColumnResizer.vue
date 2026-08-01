<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { translate as t } from '@nextcloud/l10n'
import { ref } from 'vue'
import { clampColumnWidth, COLUMN_RESIZE_STEP } from '../../utils/userListColumns.ts'

defineProps<{
	/** Column key, used as CSS variable suffix */
	column: string
	/** Human readable column label */
	label: string
}>()

const emit = defineEmits<{
	reset: []
	resize: [width: number]
	'resize-end': []
}>()

const handle = ref<HTMLElement>()

const dragging = ref(false)
const startX = ref(0)
const startWidth = ref(0)

/**
 * Current width of the header cell containing the handle
 */
function cellWidth(): number {
	const cell = handle.value?.closest('th')
	return cell?.getBoundingClientRect().width ?? 0
}

/**
 * Horizontal factor turning a pointer delta into a width delta (-1 in RTL)
 */
function resizeDirection(): number {
	return document.dir === 'rtl' ? -1 : 1
}

/**
 * Start dragging and remember the initial pointer position and column width
 *
 * @param event The pointerdown event
 */
function onPointerDown(event: PointerEvent) {
	dragging.value = true
	startX.value = event.clientX
	startWidth.value = cellWidth()
	handle.value?.setPointerCapture?.(event.pointerId)
}

/**
 * Emit the new column width while dragging
 *
 * @param event The pointermove event
 */
function onPointerMove(event: PointerEvent) {
	if (!dragging.value) {
		return
	}
	const delta = (event.clientX - startX.value) * resizeDirection()
	emit('resize', clampColumnWidth(startWidth.value + delta))
}

/**
 * Stop dragging and notify that resizing ended
 */
function onPointerUp() {
	if (!dragging.value) {
		return
	}
	dragging.value = false
	emit('resize-end')
}

/**
 * Resize the column with the arrow keys
 *
 * @param event The keydown event
 */
function onKeydown(event: KeyboardEvent) {
	const direction = { ArrowLeft: -1, ArrowRight: 1 }[event.key]
	if (direction === undefined) {
		return
	}
	event.preventDefault()
	const step = direction * COLUMN_RESIZE_STEP * resizeDirection()
	emit('resize', clampColumnWidth(cellWidth() + step))
	emit('resize-end')
}
</script>

<template>
	<span
		ref="handle"
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
		@dblclick="emit('reset')" />
</template>

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
