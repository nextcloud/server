<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="headerbar-label" :class="{ 'setting-property': isSettingProperty, 'profile-property': isProfileProperty }">
		<h3 v-if="isHeading" class="headerbar__heading">
			<!-- Already translated as required by prop validator -->
			{{ readable }}
		</h3>
		<label v-else :for="inputId">
			<!-- Already translated as required by prop validator -->
			{{ readable }}
		</label>

		<template v-if="scope">
			<FederationControl
				class="federation-control"
				:readable="readable"
				:scope.sync="localScope"
				@update:scope="onScopeChange" />
		</template>

		<template v-if="isEditable && isMultiValueSupported">
			<NcButton
				variant="tertiary"
				:disabled="!isValidSection"
				:aria-label="t('settings', 'Add additional email')"
				@click.stop.prevent="onAddAdditional">
				<template #icon>
					<Plus :size="20" />
				</template>
				{{ t('settings', 'Add') }}
			</NcButton>
		</template>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/components/NcButton'
import Plus from 'vue-material-design-icons/Plus.vue'
import FederationControl from './FederationControl.vue'
import {
	ACCOUNT_PROPERTY_READABLE_ENUM,
	PROFILE_READABLE_ENUM,
} from '../../../constants/AccountPropertyConstants.js'

export default {
	name: 'HeaderBar',

	components: {
		FederationControl,
		NcButton,
		Plus,
	},

	props: {
		scope: {
			type: String,
			default: null,
		},

		readable: {
			type: String,
			required: true,
		},

		inputId: {
			type: String,
			default: null,
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
			default: true,
		},

		isHeading: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			localScope: this.scope,
		}
	},

	computed: {
		isProfileProperty() {
			return this.readable === ACCOUNT_PROPERTY_READABLE_ENUM.PROFILE_ENABLED
		},

		isSettingProperty() {
			return !Object.values(ACCOUNT_PROPERTY_READABLE_ENUM).includes(this.readable) && !Object.values(PROFILE_READABLE_ENUM).includes(this.readable)
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
	.headerbar-label {
		align-items: center;
		color: var(--color-main-text);
		display: inline-flex;
		font-size: 16px;
		font-weight: normal;
		gap: 8px;
		margin: 12px 0 0 0;
		width: 100%;

		&.profile-property {
			height: 38px;
		}

		&.setting-property {
			height: 34px;
		}

		label {
			cursor: pointer;
		}
	}

	.headerbar__heading {
		margin: 0;
	}

	.federation-control {
		margin: 0;
	}

	.button-vue  {
		margin: 0 !important;
		margin-inline-start: auto !important;
	}
</style>
