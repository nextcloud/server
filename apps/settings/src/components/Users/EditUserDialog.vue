<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog
		class="edit-dialog"
		size="small"
		:name="t('settings', 'Edit account')"
		outTransition
		:noClose="saving"
		@closing="$emit('closing')">
		<form
			id="edit-user-form"
			class="edit-dialog__form"
			data-test="form"
			:inert="saving"
			:aria-busy="saving ? 'true' : 'false'"
			@submit.prevent="save">
			<UserFormFields
				:fieldConfig="fieldConfig"
				:errors="fieldErrors"
				:quotaOptions="quotaOptions" />
		</form>

		<template #actions>
			<NcButton
				class="edit-dialog__submit"
				data-test="submit"
				form="edit-user-form"
				variant="primary"
				type="submit"
				:aria-disabled="saving ? 'true' : 'false'">
				<template v-if="saving" #icon>
					<NcLoadingIcon />
				</template>
				{{ saving ? t('settings', 'Saving\u00A0…') : t('settings', 'Save') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script setup lang="ts">
import type { IUser } from '../../views/user-types.d.ts'
import type { QuotaOption } from './userFormUtils.ts'

import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { computed, provide, reactive, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import UserFormFields from './UserFormFields.vue'
import logger from '../../logger.ts'
import { useStore } from '../../store/index.js'
import { formDataKey } from './injectionKeys.ts'
import { diffPayload, userToFormData } from './userFormUtils.ts'

const props = defineProps<{
	/** The user being edited */
	user: IUser
	/** Quota preset options for the quota select */
	quotaOptions: QuotaOption[]
}>()

const emit = defineEmits<{
	closing: []
}>()

const store = useStore()

const allGroups = store.getters.getGroups
const serverLanguages = store.getters.getServerData.languages
const formData = userToFormData(props.user, allGroups, props.quotaOptions, serverLanguages)

/** Snapshot of initial state for diffing */
const initialData = structuredClone(formData)
// Children inject this reactive object and mutate its properties via v-model.
// Do not reassign editedUser entirely, the injected reference would go stale.
const editedUser = reactive(formData)
const saving = ref(false)
const fieldErrors = ref<Record<string, string>>({})

// Children inject editedUser and mutate its properties via v-model.
provide(formDataKey, editedUser)

const settings = computed(() => store.getters.getServerData)

const fieldConfig = computed(() => ({
	username: {
		show: true,
		disabled: true,
		label: t('settings', 'Account name'),
	},

	password: {
		show: settings.value.canChangePassword && props.user.backendCapabilities.setPassword,
		label: t('settings', 'New password'),
	},
}))

/**
 * Diff the form against its initial snapshot and submit only changed fields.
 * Maps a 422 response to per-field errors; closes the dialog on success or no-op.
 */
async function save() {
	// Guard against re-submit while a request is already running. The
	// button is only aria-disabled (not disabled), so it can still fire.
	if (saving.value) {
		return
	}
	fieldErrors.value = {}

	const payload = diffPayload(initialData, editedUser)
	if (Object.keys(payload).length === 0) {
		emit('closing')
		return
	}

	saving.value = true
	try {
		await confirmPassword()
		await store.dispatch('editUserMultiField', {
			userid: props.user.id,
			payload,
		})
		showSuccess(t('settings', 'Account updated'))
		emit('closing')
	} catch (error) {
		const errors = (error as { response?: { data?: { ocs?: { data?: { errors?: Record<string, string> } } } } })
			.response?.data?.ocs?.data?.errors
		if (errors && typeof errors === 'object') {
			fieldErrors.value = errors
		} else {
			logger.error('Failed to update account', { error })
			showError(t('settings', 'Failed to update account'))
		}
	} finally {
		saving.value = false
	}
}
</script>

<style lang="scss" scoped>
.edit-dialog {
	:deep(.dialog__actions) {
		margin-block-start: calc(var(--default-grid-baseline, 4px) * 3);
	}
}

// Visually communicate the locked/busy form while the account is saved.
.edit-dialog__form[aria-busy='true'] {
	opacity: 0.5;
}
</style>
