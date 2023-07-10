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
	<a class="profile__primary-action-button"
		:class="{ 'disabled': disabled }"
		:href="href"
		:target="target"
		rel="noopener noreferrer nofollow"
		v-on="$listeners">
		<img class="icon"
			:class="[icon, { 'icon-invert': colorPrimaryText === '#ffffff' }]"
			:src="icon">
		<slot />
	</a>
</template>

<script>
export default {
	name: 'PrimaryActionButton',

	props: {
		disabled: {
			type: Boolean,
			default: false,
		},
		href: {
			type: String,
			required: true,
		},
		icon: {
			type: String,
			required: true,
		},
		target: {
			type: String,
			required: true,
			validator: (value) => ['_self', '_blank', '_parent', '_top'].includes(value),
		},
	},

	computed: {
		colorPrimaryText() {
			// For some reason the returned string has prepended whitespace
			return getComputedStyle(document.body).getPropertyValue('--color-primary-element-text').trim()
		},
	},
}
</script>

<style lang="scss" scoped>
	.profile__primary-action-button {
		font-size: var(--default-font-size);
		font-weight: bold;
		width: 188px;
		height: 44px;
		padding: 0 16px;
		line-height: 44px;
		text-align: center;
		border-radius: var(--border-radius-pill);
		color: var(--color-primary-element-text);
		background-color: var(--color-primary-element);
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;

		.icon {
			display: inline-block;
			vertical-align: middle;
			margin-bottom: 2px;
			margin-right: 4px;

			&.icon-invert {
				filter: invert(1);
			}
		}

		&:hover,
		&:focus,
		&:active {
			background-color: var(--color-primary-element-light);
		}
	}
</style>
