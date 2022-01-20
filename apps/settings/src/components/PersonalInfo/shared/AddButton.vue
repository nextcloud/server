<!--
	- @copyright 2021, Christopher Ng <chrng8@gmail.com>
	-
	- @author Christopher Ng <chrng8@gmail.com>
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
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
-->

<template>
	<button :disabled="disabled"
		v-on="$listeners">
		<span class="icon icon-add" />
		{{ t('settings', 'Add') }}
	</button>
</template>

<script>
export default {
	name: 'AddButton',

	props: {
		disabled: {
			type: Boolean,
			default: true,
		},
	},
}
</script>

<style lang="scss" scoped>
	button {
		height: 44px;
		padding: 0 16px;
		border: none;
		background-color: transparent;

		.icon {
			margin-right: 8px;
		}

		&:enabled {
			opacity: 0.4 !important;

			.icon {
				opacity: 0.8 !important;
			}
		}

		&:hover,
		&:focus,
		&:active {
			background-color: rgba(127, 127, 127, .15);
		}

		&:enabled {
			&:hover,
			&:focus,
			&:active {
				background-color: rgba(127, 127, 127, .25);
				opacity: 0.8 !important;
			}
		}
	}
</style>
