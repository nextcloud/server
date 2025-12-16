<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<li class="predefined-status">
		<input
			:id="id"
			class="hidden-visually predefined-status__input"
			type="radio"
			name="predefined-status"
			:checked="selected"
			@change="select">
		<label class="predefined-status__label" :for="id">
			<span aria-hidden="true" class="predefined-status__label--icon">
				{{ icon }}
			</span>
			<span class="predefined-status__label--message">
				{{ message }}
			</span>
			<span class="predefined-status__label--clear-at">
				{{ formattedClearAt }}
			</span>
		</label>
	</li>
</template>

<script>
import { clearAtFormat } from '../services/clearAtService.js'

export default {
	name: 'PredefinedStatus',

	props: {
		messageId: {
			type: String,
			required: true,
		},

		icon: {
			type: String,
			required: true,
		},

		message: {
			type: String,
			required: true,
		},

		clearAt: {
			type: Object,
			required: false,
			default: null,
		},

		selected: {
			type: Boolean,
			required: false,
			default: false,
		},
	},

	emits: ['select'],

	computed: {
		id() {
			return `user-status-predefined-status-${this.messageId}`
		},

		formattedClearAt() {
			return clearAtFormat(this.clearAt)
		},
	},

	methods: {
		/**
		 * Emits an event when the user clicks the row
		 */
		select() {
			this.$emit('select')
		},
	},
}
</script>

<style lang="scss" scoped>
.predefined-status {
	&__label {
		display: flex;
		flex-wrap: nowrap;
		justify-content: flex-start;
		flex-basis: 100%;
		border-radius: var(--border-radius);
		align-items: center;
		min-height: var(--default-clickable-area);
		padding-inline: var(--default-grid-baseline);

		&, & * {
			cursor: pointer;
		}

		&:hover {
			background-color: var(--color-background-dark);
		}

		&--icon {
			flex-basis: var(--default-clickable-area);
			text-align: center;
		}

		&--message {
			font-weight: bold;
			padding: 0 6px;
		}

		&--clear-at {
			color: var(--color-text-maxcontrast);

			&::before {
				content: ' – ';
			}
		}
	}

	&__input:checked + &__label,
	&__label:active {
		outline: 2px solid var(--color-main-text);
		box-shadow: 0 0 0 4px var(--color-main-background);
		background-color: var(--color-background-dark);
		border-radius: var(--border-radius-large);
	}

	&__input:focus-visible + &__label {
		outline: 2px solid var(--color-primary-element) !important;
		background-color: var(--color-background-dark);
		border-radius: var(--border-radius-large);
	}
}
</style>
