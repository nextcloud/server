<!--
  - @copyright Copyright (c) 2020 Georg Ehrke <oc.list@georgehrke.com>
  - @author Georg Ehrke <oc.list@georgehrke.com>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<li class="predefined-status">
		<input :id="id"
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
				{{ clearAt | clearAtFilter }}
			</span>
		</label>
	</li>
</template>

<script>
import { clearAtFilter } from '../filters/clearAtFilter.js'

export default {
	name: 'PredefinedStatus',
	filters: {
		clearAtFilter,
	},
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
	computed: {
		id() {
			return `user-status-predefined-status-${this.messageId}`
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
		min-height: 44px;

		&--icon {
			flex-basis: 40px;
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
	&__input:focus + &__label,
	&__label:hover {
		background-color: var(--color-background-hover);
	}

	&__label:active {
		background-color: var(--color-background-dark);
	}
}
</style>
