<!--
	- @copyright 2021, Christopher Ng <chrng8@gmail.com>
	-
	- @author Christopher Ng <chrng8@gmail.com>
	- @author Ferdinand Thiessen <opensource@fthiessen.de>
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
	<Fragment>
		<NcActionButton v-for="federationScope in federationScopes"
			:key="federationScope.name"
			:close-after-click="true"
			:disabled="!supportedScopes.includes(federationScope.name)"
			:name="federationScope.displayName"
			type="radio"
			:value="federationScope.name"
			:model-value="scope"
			@update:modelValue="changeScope">
			<template #icon>
				<NcIconSvgWrapper :path="federationScope.icon" />
			</template>
			{{ supportedScopes.includes(federationScope.name) ? federationScope.tooltip : federationScope.tooltipDisabled }}
		</NcActionButton>
	</Fragment>
</template>

<script>
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import { loadState } from '@nextcloud/initial-state'
import { Fragment } from 'vue-frag'

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
	name: 'FederationControlActions',

	components: {
		Fragment,
		NcActionButton,
		NcIconSvgWrapper,
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
