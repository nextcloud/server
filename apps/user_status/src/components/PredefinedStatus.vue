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
	<div class="predefined-status"
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
			{{ clearAt | clearAtFilter }}
		</span>
	</div>
</template>

<script>
import { clearAtFilter } from '../filters/clearAtFilter'

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
		opacity: .7;

		&::before {
			content: ' – ';
		}
	}
}
</style>
