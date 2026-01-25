<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { computed, ref, useTemplateRef } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcFormGroup from '@nextcloud/vue/components/NcFormGroup'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import { logger } from '../utils/logger.ts'

const recoveryEnabled = defineModel<boolean>({ required: true })
const formElement = useTemplateRef('form')

const isLoading = ref(false)
const hasError = ref(false)

const password = ref('')
const confirmPassword = ref('')
const passwordMatch = computed(() => password.value === confirmPassword.value)

/**
 * Handle the form submission to enable or disable the admin recovery key
 */
async function onSubmit() {
	if (isLoading.value) {
		return
	}

	if (!passwordMatch.value) {
		return
	}

	hasError.value = false
	isLoading.value = true
	try {
		const { data } = await axios.post(
			generateUrl('/apps/encryption/ajax/adminRecovery'),
			{
				adminEnableRecovery: !recoveryEnabled.value,
				recoveryPassword: password.value,
				confirmPassword: confirmPassword.value,
			},
		)
		recoveryEnabled.value = !recoveryEnabled.value
		password.value = confirmPassword.value = ''
		formElement.value?.reset()
		if (data.data.message) {
			showSuccess(data.data.message)
		}
	} catch (error) {
		hasError.value = true
		logger.error('Failed to update recovery key settings', { error })
	} finally {
		isLoading.value = false
	}
}
</script>

<template>
	<form ref="form" @submit.prevent="onSubmit">
		<NcFormGroup
			:label="recoveryEnabled ? t('encryption', 'Disable recovery key') : t('encryption', 'Enable recovery key')"
			:description="t('encryption', 'The recovery key is an additional encryption key used to encrypt files. It is used to recover files from an account if the password is forgotten.')">
			<NcPasswordField
				v-model="password"
				required
				name="password"
				:label="t('encryption', 'Recovery key password')" />
			<NcPasswordField
				v-model="confirmPassword"
				required
				name="confirmPassword"
				:error="!!confirmPassword && !passwordMatch"
				:helperText="(passwordMatch || !confirmPassword) ? '' : t('encryption', 'Passwords fields do not match')"
				:label="t('encryption', 'Repeat recovery key password')" />

			<NcButton type="submit" :variant="recoveryEnabled ? 'error' : 'primary'">
				{{ recoveryEnabled ? t('encryption', 'Disable recovery key') : t('encryption', 'Enable recovery key') }}
			</NcButton>

			<NcNoteCard v-if="hasError" type="error">
				{{ t('encryption', 'An error occurred while updating the recovery key settings. Please try again.') }}
			</NcNoteCard>
		</NcFormGroup>
	</form>
</template>
