<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcActions ref="federationActions"
		class="federation-actions"
		:aria-label="ariaLabel"
		:disabled="disabled">
		<template #icon>
			<NcIconSvgWrapper :path="scopeIcon" />
		</template>

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
	</NcActions>
</template>

<script>
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import { loadState } from '@nextcloud/initial-state'

import {
	ACCOUNT_PROPERTY_READABLE_ENUM,
	ACCOUNT_SETTING_PROPERTY_READABLE_ENUM,
	PROFILE_READABLE_ENUM,
	PROPERTY_READABLE_KEYS_ENUM,
	PROPERTY_READABLE_SUPPORTED_SCOPES_ENUM,
	SCOPE_PROPERTY_ENUM,
	SCOPE_ENUM,
	UNPUBLISHED_READABLE_PROPERTIES,
} from '../../../constants/AccountPropertyConstants.js'
import { savePrimaryAccountPropertyScope } from '../../../service/PersonalInfo/PersonalInfoService.js'
import { handleError } from '../../../utils/handlers.ts'

const {
	federationEnabled,
	lookupServerUploadEnabled,
} = loadState('settings', 'accountParameters', {})

export default {
	name: 'FederationControl',

	components: {
		NcActions,
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

	emits: ['update:scope'],

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
			return SCOPE_PROPERTY_ENUM[this.scope].icon
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

			// TODO: provide focus method from NcActions
			this.$refs.federationActions.$refs?.triggerButton?.$el?.focus?.()
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
.federation-actions {
	&--additional {
		&:deep(button) {
			// TODO remove this hack
			height: 30px !important;
			min-height: 30px !important;
			width: 30px !important;
			min-width: 30px !important;
		}
	}
}
</style>
