<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
defineProps<{
	id: string
	label: string
	selected: boolean
	iconUrl?: string
	iconClass?: string
	sortable?: boolean
	disabled?: boolean
}>()
const emit = defineEmits<{ change: [checked: boolean] }>()
</script>

<template>
	<li class="widget-choice">
		<input
			:id="id"
			type="checkbox"
			class="checkbox"
			:checked="selected"
			:disabled="disabled"
			@change="emit('change', ($event.target as HTMLInputElement).checked)">
		<label :for="id" :class="{ draggable: sortable && selected }">
			<span v-if="$slots.icon" class="widget-choice__icon widget-choice__icon--custom" aria-hidden="true">
				<slot name="icon" />
			</span>
			<img v-else-if="iconUrl" :src="iconUrl" alt="">
			<span
				v-else-if="iconClass"
				class="widget-choice__icon"
				:class="iconClass"
				aria-hidden="true" />
			{{ label }}
		</label>
	</li>
</template>

<style lang="scss" scoped>
.widget-choice {
	label {
		position: relative;
		display: block;
		padding: 48px 16px 14px;
		margin: 8px;
		width: 140px;
		background-color: var(--color-background-hover);
		border: 2px solid var(--color-main-background);
		border-radius: var(--border-radius-large);
		text-align: start;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;

		img,
		.widget-choice__icon {
			position: absolute;
			top: 16px;
			width: 24px;
			height: 24px;
			background-size: 24px;
		}

		img,
		.widget-choice__icon:not(.widget-choice__icon--custom) {
			filter: var(--background-invert-if-dark);
		}

		&:hover {
			border-color: var(--color-primary-element);
		}
	}

	input.checkbox + label:before {
		position: absolute;
		inset-inline-end: 12px;
		top: 16px;
	}

	input:focus + label {
		border-color: var(--color-primary-element);
	}
}
</style>
