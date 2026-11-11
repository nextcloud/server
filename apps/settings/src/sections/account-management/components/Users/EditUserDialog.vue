<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { QuotaOption } from '../../../../components/Users/userFormUtils.ts'
import type { IUser } from '../../../../views/user-types.d.ts'

import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { computed, provide, reactive, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import UserFormFields from './UserFormFields.vue'
import { diffPayload, userToFormData } from '../../../../components/Users/userFormUtils.ts'
import logger from '../../../../logger.ts'
import { useUsersStore } from '../../store/users.ts'
import { formDataKey } from './injectionKeys.ts'

const props = defineProps<{
	/** The account to edit */
	user: IUser
	/** Quota presets to pick from */
	quotaOptions: QuotaOption[]
}>()

const emit = defineEmits<{
	/** The dialog was closed */
	closing: []
}>()

const usersStore = useUsersStore()

const allGroups = usersStore.groups
const serverLanguages = usersStore.serverData.languages
const formData = userToFormData(props.user, allGroups, props.quotaOptions, serverLanguages)

const initialData = structuredClone(formData)
// Children inject and mutate this object's properties; never reassign it or the
// injected reference goes stale.
const editedUser = reactive(formData)
const saving = ref(false)
const fieldErrors = ref<Record<string, string>>({})

provide(formDataKey, editedUser)

const settings = computed(() => usersStore.serverData)

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
 * Submit the changed fields; map a 422 to per-field errors, else close.
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
	} catch {
		// cancelled, nothing to do
		saving.value = false
		return
	}
	try {
		await usersStore.editUserMultiField({
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

<template>
	<NcDialog
		class="edit-dialog"
		size="small"
		:name="t('settings', 'Edit account')"
		outTransition
		:noClose="saving"
		@closing="emit('closing')">
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
