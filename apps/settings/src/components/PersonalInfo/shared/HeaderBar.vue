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
	<h3 :class="{ 'setting-property': isSettingProperty, 'profile-property': isProfileProperty }">
		<label :for="labelFor">
			<!-- Already translated as required by prop validator -->
			{{ accountProperty }}
		</label>

		<template v-if="scope">
			<FederationControl class="federation-control"
				:account-property="accountProperty"
				:scope.sync="localScope"
				@update:scope="onScopeChange" />
		</template>

		<template v-if="isEditable && isMultiValueSupported">
			<AddButton class="add-button"
				:disabled="!isValidSection"
				@click.stop.prevent="onAddAdditional" />
		</template>
	</h3>
</template>

<script>
import AddButton from './AddButton'
import FederationControl from './FederationControl'

import { ACCOUNT_PROPERTY_READABLE_ENUM, ACCOUNT_SETTING_PROPERTY_READABLE_ENUM, PROFILE_READABLE_ENUM } from '../../../constants/AccountPropertyConstants'

export default {
	name: 'HeaderBar',

	components: {
		AddButton,
		FederationControl,
	},

	props: {
		accountProperty: {
			type: String,
			required: true,
			validator: (value) => Object.values(ACCOUNT_PROPERTY_READABLE_ENUM).includes(value) || Object.values(ACCOUNT_SETTING_PROPERTY_READABLE_ENUM).includes(value) || value === PROFILE_READABLE_ENUM.PROFILE_VISIBILITY,
		},
		isEditable: {
			type: Boolean,
			default: true,
		},
		isMultiValueSupported: {
			type: Boolean,
			default: false,
		},
		isValidSection: {
			type: Boolean,
			default: false,
		},
		labelFor: {
			type: String,
			default: '',
		},
		scope: {
			type: String,
			default: null,
		},
	},

	data() {
		return {
			localScope: this.scope,
		}
	},

	computed: {
		isProfileProperty() {
			return this.accountProperty === ACCOUNT_PROPERTY_READABLE_ENUM.PROFILE_ENABLED
		},

		isSettingProperty() {
			return Object.values(ACCOUNT_SETTING_PROPERTY_READABLE_ENUM).includes(this.accountProperty)
		},
	},

	methods: {
		onAddAdditional() {
			this.$emit('add-additional')
		},

		onScopeChange(scope) {
			this.$emit('update:scope', scope)
		},
	},
}
</script>

<style lang="scss" scoped>
	h3 {
		display: inline-flex;
		width: 100%;
		margin: 12px 0 0 0;
		font-size: 16px;
		color: var(--color-text-light);

		&.profile-property {
			height: 38px;
		}

		&.setting-property {
			height: 32px;
		}

		label {
			cursor: pointer;
		}
	}

	.federation-control {
		margin: -12px 0 0 8px;
	}

	.add-button {
		margin: -12px 0 0 auto !important;
	}
</style>
