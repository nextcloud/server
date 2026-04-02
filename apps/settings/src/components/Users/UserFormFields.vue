<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user-form-fields">
		<NcTextField
			v-if="fieldConfig.username?.show"
			ref="username"
			v-model="formData.username"
			class="user-form-fields__item"
			data-test="username"
			:disabled="fieldConfig.username?.disabled"
			:label="fieldConfig.username?.label"
			autocapitalize="none"
			autocomplete="off"
			spellcheck="false"
			pattern="[a-zA-Z0-9 _\.@\-']+"
			:required="fieldConfig.username?.required" />

		<NcTextField
			v-model="formData.displayName"
			class="user-form-fields__item"
			data-test="displayName"
			:label="t('settings', 'Display name')"
			:error="!!errors.displayName"
			:helper-text="errors.displayName"
			autocapitalize="none"
			autocomplete="off"
			spellcheck="false" />

		<span
			v-if="fieldConfig.showPasswordEmailHint"
			id="password-email-hint"
			class="user-form-fields__hint">
			{{ t('settings', 'Either password or email is required') }}
		</span>

		<NcPasswordField
			v-if="fieldConfig.password?.show !== false"
			ref="password"
			v-model="formData.password"
			class="user-form-fields__item"
			data-test="password"
			:minlength="minPasswordLength"
			:maxlength="469"
			:aria-describedby="fieldConfig.showPasswordEmailHint ? 'password-email-hint' : undefined"
			:label="fieldConfig.password?.label"
			:error="!!errors.password"
			:helper-text="errors.password"
			autocapitalize="none"
			autocomplete="new-password"
			spellcheck="false"
			:required="fieldConfig.password?.required" />

		<NcTextField
			v-model="formData.email"
			class="user-form-fields__item"
			data-test="email"
			type="email"
			:aria-describedby="fieldConfig.showPasswordEmailHint ? 'password-email-hint' : undefined"
			:label="fieldConfig.email?.label || t('settings', 'Email')"
			:error="!!errors.email"
			:helper-text="errors.email"
			autocapitalize="none"
			autocomplete="off"
			spellcheck="false"
			:required="fieldConfig.email?.required" />

		<UserFormGroups />
		<UserFormQuota :quota-options="quotaOptions" />
		<UserFormLanguage />
		<UserFormManager />

		<!-- Catch-all for validation errors on NcSelect-based fields (groups, quota, etc.) -->
		<div
			v-if="Object.keys(unhandledErrors).length > 0"
			class="user-form-fields__error-summary"
			aria-live="polite"
			role="status">
			<p v-for="(message, field) in unhandledErrors" :key="field">
				{{ field }}: {{ message }}
			</p>
		</div>
	</div>
</template>

<script>
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import UserFormGroups from './UserFormGroups.vue'
import UserFormLanguage from './UserFormLanguage.vue'
import UserFormManager from './UserFormManager.vue'
import UserFormQuota from './UserFormQuota.vue'

/**
 * Shared form fields for creating and editing user accounts.
 *
 * Injects a reactive `formData` object (provided by the parent dialog)
 * and binds directly to its properties via v-model. Complex field logic
 * (groups, quota, language, manager) is delegated to dedicated sub-components
 * that also inject the same formData.
 *
 * Expected formData shape:
 *   { username, displayName, password, email, groups, subadminGroups, quota, language, manager }
 */
export default {
	name: 'UserFormFields',

	components: {
		NcPasswordField,
		NcTextField,
		UserFormGroups,
		UserFormLanguage,
		UserFormManager,
		UserFormQuota,
	},

	inject: ['formData'],

	props: {
		/** Quota preset options for the quota select */
		quotaOptions: {
			type: Array,
			required: true,
		},

		/**
		 * Per-field configuration for visibility, labels, and required state.
		 * Only fields that differ from defaults need to be specified.
		 *
		 * Example: { username: { show: true, label: 'Account name', required: true },
		 *            password: { show: true, label: 'Password', required: false },
		 *            email: { label: 'Email (required)', required: true },
		 *            showPasswordEmailHint: true }
		 */
		fieldConfig: {
			type: Object,
			default: () => ({}),
		},

		/** Per-field error messages from 422 validation (e.g. { email: 'Invalid' }) */
		errors: {
			type: Object,
			default: () => ({}),
		},
	},

	computed: {
		minPasswordLength() {
			return this.$store.getters.getPasswordPolicyMinLength
		},

		unhandledErrors() {
			const handled = new Set(['displayName', 'password', 'email'])
			return Object.fromEntries(Object.entries(this.errors).filter(([key]) => !handled.has(key)))
		},
	},

	methods: {
		focusField(name) {
			this.$refs[name]?.focus?.()
		},
	},
}
</script>

<style lang="scss" scoped>
.user-form-fields {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 4px 0;

	&__item {
		width: 100%;

		&:not(:focus):not(:active) {
			border-color: var(--color-border-dark);
		}
	}

	&__hint {
		color: var(--color-text-maxcontrast);
		margin-top: 8px;
		align-self: flex-start;
	}

	// Reach into sub-component root elements to apply consistent sizing
	:deep(.user-form__item) {
		width: 100%;
	}

	:deep(.user-form__select) {
		width: 100%;
	}

	:deep(.user-form__managers) {
		margin-bottom: 12px;
	}

	&__error-summary {
		width: 100%;
		margin-top: 8px;
		color: var(--color-error);
		font-size: var(--default-font-size, 0.875rem);

		p {
			margin: 2px 0;
		}
	}
}
</style>
