<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcActions class="sharing-entry__actions"
		:aria-label="actionsTooltip"
		menu-align="right"
		:open="localOpen"
		@close="onCancel">
		<!-- pending data menu -->
		<NcActionText v-if="errors.pending"
			class="error">
			<template #icon>
				<ErrorIcon :size="20" />
			</template>
			{{ errors.pending }}
		</NcActionText>
		<NcActionText v-else icon="icon-info">
			{{ t('files_sharing', 'Please enter the following required information before creating the share') }}
		</NcActionText>

		<!-- password -->
		<NcActionCheckbox v-if="pendingPassword"
			:checked="localIsPasswordProtected"
			:disabled="config.enforcePasswordForPublicLink || saving"
			class="share-link-password-checkbox"
			@update:checked="onPasswordProtectedChange">
			{{ config.enforcePasswordForPublicLink ? t('files_sharing', 'Password protection (enforced)') : t('files_sharing', 'Password protection') }}
		</NcActionCheckbox>

		<NcActionInput v-if="pendingEnforcedPassword || share.password"
			class="share-link-password"
			:label="t('files_sharing', 'Enter a password')"
			:value="share.password"
			:disabled="saving"
			:required="config.enableLinkPasswordByDefault || config.enforcePasswordForPublicLink"
			:minlength="isPasswordPolicyEnabled && config.passwordPolicy.minLength"
			autocomplete="new-password"
			@submit="onNewLinkShare(true)">
			<template #icon>
				<LockIcon :size="20" />
			</template>
		</NcActionInput>

		<NcActionCheckbox v-if="pendingDefaultExpirationDate"
			:checked="localDefaultExpirationDateEnabled"
			:disabled="pendingEnforcedExpirationDate || saving"
			class="share-link-expiration-date-checkbox"
			@update:checked="onExpirationDateToggleChange">
			{{ config.isDefaultExpireDateEnforced ? t('files_sharing', 'Enable link expiration (enforced)') : t('files_sharing', 'Enable link expiration') }}
		</NcActionCheckbox>

		<!-- expiration date -->
		<NcActionInput v-if="(pendingDefaultExpirationDate || pendingEnforcedExpirationDate) && localDefaultExpirationDateEnabled"
			data-cy-files-sharing-expiration-date-input
			class="share-link-expire-date"
			:label="pendingEnforcedExpirationDate ? t('files_sharing', 'Enter expiration date (enforced)') : t('files_sharing', 'Enter expiration date')"
			:disabled="saving"
			:is-native-picker="true"
			:hide-label="true"
			:value="new Date(share.expireDate)"
			type="date"
			:min="dateTomorrow"
			:max="maxExpirationDateEnforced"
			@change="expirationDateChanged($event)">
			<template #icon>
				<IconCalendarBlank :size="20" />
			</template>
		</NcActionInput>

		<NcActionButton @click.prevent.stop="onNewLinkShare(true)">
			<template #icon>
				<CheckIcon :size="20" />
			</template>
			{{ t('files_sharing', 'Create share') }}
		</NcActionButton>
		<NcActionButton @click.prevent.stop="onCancel">
			<template #icon>
				<CloseIcon :size="20" />
			</template>
			{{ t('files_sharing', 'Cancel') }}
		</NcActionButton>
	</NcActions>
</template>

<script>
import { NcActions, NcActionButton, NcActionCheckbox, NcActionInput, NcActionText } from '@nextcloud/vue'
import ErrorIcon from 'vue-material-design-icons/Exclamation.vue'
import LockIcon from 'vue-material-design-icons/Lock.vue'
import CheckIcon from 'vue-material-design-icons/CheckBold.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import IconCalendarBlank from 'vue-material-design-icons/CalendarBlank.vue'

export default {
	name: 'PendingActions',

	components: {
		NcActions,
		NcActionButton,
		NcActionCheckbox,
		NcActionInput,
		NcActionText,
		ErrorIcon,
		LockIcon,
		CheckIcon,
		CloseIcon,
		IconCalendarBlank,
	},

	props: {
		open: {
			type: Boolean,
			required: true,
		},
		share: {
			type: Object,
			required: true,
		},
		config: {
			type: Object,
			required: true,
		},
		errors: {
			type: Object,
			required: true,
		},
		pendingPassword: {
			type: Boolean,
			required: true,
		},
		pendingEnforcedPassword: {
			type: Boolean,
			required: true,
		},
		pendingDefaultExpirationDate: {
			type: Boolean,
			required: true,
		},
		pendingEnforcedExpirationDate: {
			type: Boolean,
			required: true,
		},
		defaultExpirationDateEnabled: {
			type: Boolean,
			required: true,
		},
		saving: {
			type: Boolean,
			required: true,
		},
		isPasswordPolicyEnabled: {
			type: Boolean,
			required: true,
		},
		dateTomorrow: {
			type: Date,
			required: true,
		},
		maxExpirationDateEnforced: {
			type: String,
			default: '',
		},
		actionsTooltip: {
			type: String,
			required: true,
		},
		isPasswordProtected: {
			type: Boolean,
			required: true,
		},
	},

	data() {
		return {
			// Local state for checkboxes
			localIsPasswordProtected: this.isPasswordProtected,
			localDefaultExpirationDateEnabled: this.defaultExpirationDateEnabled,
			// Local copy of the open prop
			localOpen: this.open,
		}
	},

	watch: {
		// Sync changes from parent to local state
		isPasswordProtected(newVal) {
			this.localIsPasswordProtected = newVal
		},
		defaultExpirationDateEnabled(newVal) {
			this.localDefaultExpirationDateEnabled = newVal
		},
		open(newVal) {
			this.localOpen = newVal
		},
		// Sync changes from localOpen to parent
		localOpen(newVal) {
			this.$emit('update:open', newVal)
		},
	},

	methods: {
		onNewLinkShare(shareReviewComplete) {
			this.$emit('new-link-share', shareReviewComplete)
		},
		onCancel() {
			this.$emit('cancel')
		},
		onPasswordDisable() {
			this.$emit('password-disable')
		},
		onExpirationDateToggleChange(enabled) {
			this.localDefaultExpirationDateEnabled = enabled
			this.$emit('update:defaultExpirationDateEnabled', enabled)
		},
		onPasswordProtectedChange(enabled) {
			this.localIsPasswordProtected = enabled
			this.$emit('update:isPasswordProtected', enabled)
		},
		expirationDateChanged(event) {
			this.$emit('expiration-date-changed', event)
		},
	},
}
</script>

<style lang="scss" scoped>
.sharing-entry__actions {
	.action-item {
		~.action-item {
			margin-inline-start: 0
		}
	}
}
</style>
