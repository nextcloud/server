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
-->

<template>
	<Actions
		class="actions-federation"
		:aria-label="t('settings', 'Change privacy level of email')"
		:default-icon="scopeIcon"
		:disabled="disabled">
		<ActionButton v-for="federationScope in federationScopes"
			:key="federationScope.name"
			class="forced-action"
			:class="{ 'forced-active': scope === federationScope.name }"
			:aria-label="federationScope.tooltip"
			:close-after-click="true"
			:icon="federationScope.iconClass"
			:title="federationScope.displayName"
			@click.stop.prevent="changeScope(federationScope.name)">
			{{ federationScope.tooltip }}
		</ActionButton>
	</Actions>
</template>

<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import { loadState } from '@nextcloud/initial-state'
import { showError } from '@nextcloud/dialogs'

import { SCOPE_ENUM, SCOPE_PROPERTY_ENUM } from '../../../constants/AccountPropertyConstants'
import { savePrimaryEmailScope, saveAdditionalEmailScope } from '../../../service/PersonalInfoService'

const { lookupServerUploadEnabled } = loadState('settings', 'accountParameters', {})

// TODO hardcoded for email, should abstract this for other sections
const excludedScopes = [SCOPE_ENUM.PRIVATE]

export default {
	name: 'FederationControl',

	components: {
		Actions,
		ActionButton,
	},

	props: {
		primary: {
			type: Boolean,
			default: false,
		},
		email: {
			type: String,
			default: '',
		},
		scope: {
			type: String,
			required: true,
		},
		disabled: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			initialScope: this.scope,
		}
	},

	computed: {
		federationScopes() {
			return Object.values(SCOPE_PROPERTY_ENUM).filter(({ name }) => !this.unsupportedScopes.includes(name))
		},

		unsupportedScopes() {
			if (!lookupServerUploadEnabled) {
				return [
					...excludedScopes,
					SCOPE_ENUM.FEDERATED,
					SCOPE_ENUM.PUBLISHED,
				]
			}

			return excludedScopes
		},

		scopeIcon() {
			return SCOPE_PROPERTY_ENUM[this.scope].iconClass
		},
	},

	methods: {
		async changeScope(scope) {
			this.$emit('update:scope', scope)

			this.$nextTick(async () => {
				if (this.primary) {
					await this.updatePrimaryEmailScope()
				} else {
					await this.updateAdditionalEmailScope()
				}
			})
		},

		async updatePrimaryEmailScope() {
			try {
				const responseData = await savePrimaryEmailScope(this.scope)
				this.handleResponse(responseData.ocs?.meta?.status)
			} catch (e) {
				this.handleResponse('error', 'Unable to update federation scope of the primary email', e)
			}
		},

		async updateAdditionalEmailScope() {
			try {
				const responseData = await saveAdditionalEmailScope(this.email, this.scope)
				this.handleResponse(responseData.ocs?.meta?.status)
			} catch (e) {
				this.handleResponse('error', 'Unable to update federation scope of additional email', e)
			}
		},

		handleResponse(status, errorMessage, error) {
			if (status === 'ok') {
				this.initialScope = this.scope
			} else {
				this.$emit('update:scope', this.initialScope)
				showError(t('settings', errorMessage))
				this.logger.error(errorMessage, error)
			}
		},
	},
}
</script>

<style lang="scss" scoped>
	.actions-federation {
		opacity: 0.4 !important;

		&:hover {
			opacity: 0.8 !important;
		}
	}

	.forced-active {
		background-color: var(--color-primary-light) !important;
		box-shadow: inset 2px 0 var(--color-primary) !important;
	}

	.forced-action {
		&::v-deep p {
			width: 150px !important;
			padding: 8px 0 !important;
			color: var(--color-main-text) !important;
			font-size: 12.8px !important;
			line-height: 1.5em !important;
		}
	}
</style>
