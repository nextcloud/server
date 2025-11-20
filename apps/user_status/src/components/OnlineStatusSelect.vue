<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="user-status-online-select">
		<input
			:id="id"
			:checked="checked"
			class="hidden-visually user-status-online-select__input"
			type="radio"
			name="user-status-online"
			@change="onChange">
		<label :for="id" class="user-status-online-select__label">
			<span class="user-status-online-select__icon-wrapper">
				<NcUserStatusIcon
					:status="type"
					class="user-status-online-select__icon"
					aria-hidden="true" />
			</span>
			{{ label }}
			<em class="user-status-online-select__subline">{{ subline }}</em>
		</label>
	</div>
</template>

<script>
import NcUserStatusIcon from '@nextcloud/vue/components/NcUserStatusIcon'

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

	emits: ['select'],

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
.user-status-online-select {
	&__label {
		box-sizing: inherit;
		display: grid;
		grid-template-columns: var(--default-clickable-area) 1fr 2fr;
		align-items: center;
		gap: var(--default-grid-baseline);
		min-height: var(--default-clickable-area);
		padding: var(--default-grid-baseline);
		border-radius: var(--border-radius-large);
		background-color: var(--color-background-hover);

		&, & * {
			cursor: pointer;
		}

		&:hover {
			background-color: var(--color-background-dark);
		}
	}

	&__icon-wrapper {
		height: var(--default-clickable-area);
		width: var(--default-clickable-area);
		display: flex;
		align-items: center;
		justify-content: center;
	}

	&__icon {
		height: 20px;
		width: 20px;
	}

	&__input:checked + &__label {
		outline: 2px solid var(--color-main-text);
		background-color: var(--color-background-dark);
		box-shadow: 0 0 0 4px var(--color-main-background);
	}

	&__input:focus-visible + &__label {
		outline: 2px solid var(--color-primary-element) !important;
		background-color: var(--color-background-dark);
	}

	&__subline {
		display: block;
		color: var(--color-text-maxcontrast);
	}
}
</style>
