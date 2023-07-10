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
	<NcActions :class="{ 'federation-actions': !additional, 'federation-actions--additional': additional }"
		:aria-label="ariaLabel"
		:default-icon="scopeIcon"
		:disabled="disabled">
		<FederationControlAction v-for="federationScope in federationScopes"
			:key="federationScope.name"
			:active-scope="scope"
			:display-name="federationScope.displayName"
			:handle-scope-change="changeScope"
			:icon-class="federationScope.iconClass"
			:is-supported-scope="supportedScopes.includes(federationScope.name)"
			:name="federationScope.name"
			:tooltip-disabled="federationScope.tooltipDisabled"
			:tooltip="federationScope.tooltip"
			:aria-label="federationScope.tooltip" />
	</NcActions>
</template>

<script>
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import { loadState } from '@nextcloud/initial-state'

import FederationControlAction from './FederationControlAction.vue'

import {
	ACCOUNT_PROPERTY_READABLE_ENUM,
	ACCOUNT_SETTING_PROPERTY_READABLE_ENUM,
	PROFILE_READABLE_ENUM,
	PROPERTY_READABLE_KEYS_ENUM,
	PROPERTY_READABLE_SUPPORTED_SCOPES_ENUM,
	SCOPE_ENUM, SCOPE_PROPERTY_ENUM,
	UNPUBLISHED_READABLE_PROPERTIES,
} from '../../../constants/AccountPropertyConstants.js'
import { savePrimaryAccountPropertyScope } from '../../../service/PersonalInfo/PersonalInfoService.js'
import { handleError } from '../../../utils/handlers.js'

const {
	federationEnabled,
	lookupServerUploadEnabled,
} = loadState('settings', 'accountParameters', {})

export default {
	name: 'FederationControl',

	components: {
		NcActions,
		FederationControlAction,
	},

	props: {
		readable: {
			type: String,
			required: true,
			validator: (value) => Object.values(ACCOUNT_PROPERTY_READABLE_ENUM).includes(value) || Object.values(ACCOUNT_SETTING_PROPERTY_READABLE_ENUM).includes(value) || value === PROFILE_READABLE_ENUM.PROFILE_VISIBILITY,
		},
		additional: {
			type: Boolean,
			default: false,
		},
		additionalValue: {
			type: String,
			default: '',
		},
		disabled: {
			type: Boolean,
			default: false,
		},
		handleAdditionalScopeChange: {
			type: Function,
			default: null,
		},
		scope: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			readableLowerCase: this.readable.toLocaleLowerCase(),
			initialScope: this.scope,
		}
	},

	computed: {
		ariaLabel() {
			return t('settings', 'Change scope level of {property}, current scope is {scope}', { property: this.readableLowerCase, scope: this.scopeDisplayNameLowerCase })
		},

		scopeDisplayNameLowerCase() {
			return SCOPE_PROPERTY_ENUM[this.scope].displayName.toLocaleLowerCase()
		},

		scopeIcon() {
			return SCOPE_PROPERTY_ENUM[this.scope].iconClass
		},

		federationScopes() {
			return Object.values(SCOPE_PROPERTY_ENUM)
		},

		supportedScopes() {
			const scopes = PROPERTY_READABLE_SUPPORTED_SCOPES_ENUM[this.readable]

			if (UNPUBLISHED_READABLE_PROPERTIES.includes(this.readable)) {
				return scopes
			}

			if (federationEnabled) {
				scopes.push(SCOPE_ENUM.FEDERATED)
			}

			if (lookupServerUploadEnabled) {
				scopes.push(SCOPE_ENUM.PUBLISHED)
			}

			return scopes
		},
	},

	methods: {
		async changeScope(scope) {
			this.$emit('update:scope', scope)

			if (!this.additional) {
				await this.updatePrimaryScope(scope)
			} else {
				await this.updateAdditionalScope(scope)
			}
		},

		async updatePrimaryScope(scope) {
			try {
				const responseData = await savePrimaryAccountPropertyScope(PROPERTY_READABLE_KEYS_ENUM[this.readable], scope)
				this.handleResponse({
					scope,
					status: responseData.ocs?.meta?.status,
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to update federation scope of the primary {property}', { property: this.readableLowerCase }),
					error: e,
				})
			}
		},

		async updateAdditionalScope(scope) {
			try {
				const responseData = await this.handleAdditionalScopeChange(this.additionalValue, scope)
				this.handleResponse({
					scope,
					status: responseData.ocs?.meta?.status,
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to update federation scope of additional {property}', { property: this.readableLowerCase }),
					error: e,
				})
			}
		},

		handleResponse({ scope, status, errorMessage, error }) {
			if (status === 'ok') {
				this.initialScope = scope
			} else {
				this.$emit('update:scope', this.initialScope)
				handleError(error, errorMessage)
			}
		},
	},
}
</script>

<style lang="scss" scoped>
	.federation-actions,
	.federation-actions--additional {
		opacity: 0.4 !important;

		&:hover,
		&:focus,
		&:active {
			opacity: 0.8 !important;
		}
	}

	.federation-actions--additional {
		&::v-deep button {
			// TODO remove this hack
			padding-bottom: 7px;
			height: 30px !important;
			min-height: 30px !important;
			width: 30px !important;
			min-width: 30px !important;
		}
	}
</style>
