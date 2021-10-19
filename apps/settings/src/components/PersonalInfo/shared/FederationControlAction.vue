<!--
	- @copyright 2021 Christopher Ng <chrng8@gmail.com>
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
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
-->

<template>
	<ActionButton
		:aria-label="isSupportedScope ? tooltip : tooltipDisabled"
		class="federation-actions__btn"
		:class="{ 'federation-actions__btn--active': activeScope === name }"
		:close-after-click="true"
		:disabled="!isSupportedScope"
		:icon="iconClass"
		:title="displayName"
		@click.stop.prevent="updateScope">
		{{ isSupportedScope ? tooltip : tooltipDisabled }}
	</ActionButton>
</template>

<script>
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'

export default {
	name: 'FederationControlAction',

	components: {
		ActionButton,
	},

	props: {
		activeScope: {
			type: String,
			required: true,
		},
		displayName: {
			type: String,
			required: true,
		},
		handleScopeChange: {
			type: Function,
			default: () => {},
		},
		iconClass: {
			type: String,
			required: true,
		},
		isSupportedScope: {
			type: Boolean,
			required: true,
		},
		name: {
			type: String,
			required: true,
		},
		tooltipDisabled: {
			type: String,
			default: '',
		},
		tooltip: {
			type: String,
			required: true,
		},
	},

	methods: {
		updateScope() {
			this.handleScopeChange(this.name)
		},
	},
}
</script>

<style lang="scss" scoped>
	.federation-actions__btn {
		&::v-deep p {
			width: 150px !important;
			padding: 8px 0 !important;
			color: var(--color-main-text) !important;
			font-size: 12.8px !important;
			line-height: 1.5em !important;
		}
	}

	.federation-actions__btn--active {
		background-color: var(--color-primary-light) !important;
		box-shadow: inset 2px 0 var(--color-primary) !important;
	}
</style>
