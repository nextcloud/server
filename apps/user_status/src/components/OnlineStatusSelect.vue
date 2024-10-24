<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="user-status-online-select">
		<input :id="id"
			:checked="checked"
			class="hidden-visually user-status-online-select__input"
			type="radio"
			name="user-status-online"
			@change="onChange">
		<label :for="id" class="user-status-online-select__label">
			{{ label }}
			<NcUserStatusIcon :status="type"
				aria-hidden="true" />
			<em class="user-status-online-select__subline">{{ subline }}</em>
		</label>
	</div>
</template>

<script>
import NcUserStatusIcon from '@nextcloud/vue/dist/Components/NcUserStatusIcon.js'

export default {
	name: 'OnlineStatusSelect',

	components: {
		NcUserStatusIcon,
	},

	props: {
		checked: {
			type: Boolean,
			default: false,
		},
		type: {
			type: String,
			required: true,
		},
		label: {
			type: String,
			required: true,
		},
		subline: {
			type: String,
			default: null,
		},
	},

	computed: {
		id() {
			return `user-status-online-status-${this.type}`
		},
	},

	methods: {
		onChange() {
			this.$emit('select', this.type)
		},
	},
}
</script>

<style lang="scss" scoped>
@use 'sass:math';
$icon-size: 24px;
$label-padding: 8px;

.user-status-online-select {
	&__label {
		position: relative;
		display: block;
		margin: $label-padding;
		padding: $label-padding;
		padding-inline-start: $icon-size + $label-padding * 2;
		border: 2px solid var(--color-main-background);
		border-radius: var(--border-radius-large);
		background-color: var(--color-background-hover);
		background-position: $label-padding center;
		background-size: $icon-size;

		span,
		& {
			cursor: pointer;
		}

		span {
			position: absolute;
			top: calc(50% - 10px);
			inset-inline-start: 10px;
			display: block;
			width: $icon-size;
			height: $icon-size;
		}
	}

	&__input:checked + &__label {
		outline: 2px solid var(--color-main-text);
		box-shadow: 0 0 0 4px var(--color-main-background);
	}

	&__input:focus-visible + &__label {
		outline: 2px solid var(--color-primary-element) !important;
	}

	&__subline {
		display: block;
		color: var(--color-text-lighter);
	}
}
</style>
