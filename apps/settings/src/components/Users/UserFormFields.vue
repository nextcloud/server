<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user-form-fields">
		<!-- Static display for non-editable username (edit dialog) -->
		<div
			v-if="fieldConfig.username?.show && fieldConfig.username?.disabled"
			class="user-form-fields__item user-form-fields__static"
			data-test="username">
			<span class="user-form-fields__static-label">
				{{ fieldConfig.username?.label }}
			</span>
			<span class="user-form-fields__static-value">
				{{ formData.username }}
			</span>
		</div>

		<!-- Editable username input (create dialog) -->
		<NcTextField
			v-else-if="fieldConfig.username?.show"
			ref="username"
			v-model="formData.username"
			class="user-form-fields__item"
			data-test="username"
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
			:helperText="errors.displayName"
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
			:helperText="errors.password"
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
			:helperText="errors.email"
			autocapitalize="none"
			autocomplete="off"
			spellcheck="false"
			:required="fieldConfig.email?.required" />

		<UserFormGroups />
		<UserFormQuota :quotaOptions="quotaOptions" />
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

<script setup lang="ts">
import type { QuotaOption } from './userFormUtils.ts'

import { translate as t } from '@nextcloud/l10n'
import { computed, inject, ref } from 'vue'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import UserFormGroups from './UserFormGroups.vue'
import UserFormLanguage from './UserFormLanguage.vue'
import UserFormManager from './UserFormManager.vue'
import UserFormQuota from './UserFormQuota.vue'
import { useStore } from '../../store/index.js'
import { formDataKey } from './injectionKeys.ts'

/** Per-field configuration for visibility, labels, and required state */
interface FieldConfig {
	username?: { show?: boolean, disabled?: boolean, label?: string, required?: boolean }
	password?: { show?: boolean, label?: string, required?: boolean }
	email?: { label?: string, required?: boolean }
	showPasswordEmailHint?: boolean
}

/**
 * Shared form fields for creating and editing user accounts.
 *
 * Injects a reactive `formData` object (provided by the parent dialog)
 * and binds directly to its properties via v-model. Complex field logic
 * (groups, quota, language, manager) is delegated to dedicated sub-components
 * that also inject the same formData.
 */
const props = withDefaults(defineProps<{
	/** Quota preset options for the quota select */
	quotaOptions: QuotaOption[]
	/** Per-field configuration; only fields differing from defaults need specifying */
	fieldConfig?: FieldConfig
	/** Per-field error messages from 422 validation (e.g. { email: 'Invalid' }) */
	errors?: Record<string, string>
}>(), {
	fieldConfig: () => ({}),
	errors: () => ({}),
})

const store = useStore()

/** Shared, reactive form state provided by the parent dialog */
const formData = inject(formDataKey)!

/** Template refs used by the parent dialog to focus a field on error */
const username = ref<{ focus?: () => void } | null>(null)
const password = ref<{ focus?: () => void } | null>(null)

const minPasswordLength = computed(() => store.getters.getPasswordPolicyMinLength)

/** Errors not bound to a dedicated input, surfaced in the catch-all live region */
const unhandledErrors = computed(() => {
	const handled = new Set(['displayName', 'password', 'email'])
	return Object.fromEntries(Object.entries(props.errors).filter(([key]) => !handled.has(key)))
})

/**
 * Focus a named field. Called by the parent dialog (e.g. on 422 to focus the
 * offending input, or on mount to focus the username).
 *
 * @param name The field to focus
 */
function focusField(name: 'username' | 'password') {
	const field = name === 'username' ? username : password
	field.value?.focus?.()
}

defineExpose({ focusField })
</script>

<style lang="scss" scoped>
.user-form-fields {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: calc(var(--default-grid-baseline, 4px) * 2) 0;

	&__item {
		width: 100%;

		&:not(:focus):not(:active) {
			border-color: var(--color-border-dark);
		}
	}

	&__static {
		display: flex;
		flex-direction: column;
		justify-content: center;
		min-height: var(--default-clickable-area, 44px);
		padding: var(--border-width-input-focused, 2px);
		padding-inline: calc(var(--border-radius-element, 8px) + var(--border-width-input-focused, 2px));

		// Manually align static value with inputs below until we have a static field in component lib.
		// See: https://github.com/nextcloud/server/issues/53862#issuecomment-4212613996
		margin-left: 18px;

		&-label {
			font-size: var(--font-size-small, 13px);
			font-weight: 500;
			line-height: 1.5;
			color: var(--color-text-maxcontrast);
		}

		&-value {
			font-size: var(--default-font-size, 14px);
			line-height: 1.5;
			color: var(--color-main-text);
		}
	}

	&__hint {
		color: var(--color-text-maxcontrast);
		margin-block-start: calc(var(--default-grid-baseline, 4px) * 2);
		align-self: flex-start;
	}

	// Reach into sub-component root elements to apply consistent sizing
	:deep(.user-form__item) {
		width: 100%;
	}

	:deep(.user-form__select) {
		width: 100%;
	}

	&__error-summary {
		width: 100%;
		margin-block-start: calc(var(--default-grid-baseline, 4px) * 2);
		color: var(--color-error);
		font-size: var(--default-font-size, 14px);

		p {
			margin-block: calc(var(--default-grid-baseline, 4px) / 2);
		}
	}
}
</style>
