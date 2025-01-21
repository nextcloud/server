<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="predefined-status backup-status"
		tabindex="0"
		@keyup.enter="select"
		@keyup.space="select"
		@click="select">
		<span class="predefined-status__icon">
			{{ icon }}
		</span>
		<span class="predefined-status__message">
			{{ message }}
		</span>
		<span class="predefined-status__clear-at">
			{{ $t('user_status', 'Previously set') }}
		</span>

		<div class="backup-status__reset-button">
			<NcButton @click="select">
				{{ $t('user_status', 'Reset status') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'

export default {
	name: 'PreviousStatus',

	components: {
		NcButton,
	},

	props: {
		icon: {
			type: [String, null],
			required: true,
		},
		message: {
			type: String,
			required: true,
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
	display: flex;
	flex-wrap: nowrap;
	justify-content: flex-start;
	flex-basis: 100%;
	border-radius: var(--border-radius);
	align-items: center;
	min-height: 44px;

	&:hover,
	&:focus {
		background-color: var(--color-background-hover);
	}

	&:active{
		background-color: var(--color-background-dark);
	}

	&__icon {
		flex-basis: 40px;
		text-align: center;
	}

	&__message {
		font-weight: bold;
		padding: 0 6px;
	}

	&__clear-at {
		color: var(--color-text-maxcontrast);

		&::before {
			content: ' – ';
		}
	}
}

.backup-status {
	&__reset-button {
		justify-content: flex-end;
		display: flex;
		flex-grow: 1;
	}
}
</style>
