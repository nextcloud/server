<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import axios, { isAxiosError } from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcFormGroup, NcNoteCard, NcPasswordField } from '@nextcloud/vue'
import { ref, useTemplateRef } from 'vue'

defineProps<{
	recoveryEnabledForUser: boolean
}>()

const emit = defineEmits<{
	updated: []
}>()

const formElement = useTemplateRef('form')

const isLoading = ref(false)
const hasError = ref(false)
const oldPrivateKeyPassword = ref('')
const newPrivateKeyPassword = ref('')

/**
 * Handle the form submission to change the private key password
 */
async function onSubmit() {
	if (isLoading.value) {
		return
	}

	isLoading.value = true
	hasError.value = false
	try {
		await axios.post(
			generateUrl('/apps/encryption/ajax/updatePrivateKeyPassword'),
			{
				oldPassword: oldPrivateKeyPassword.value,
				newPassword: newPrivateKeyPassword.value,
			},
		)
		oldPrivateKeyPassword.value = newPrivateKeyPassword.value = ''
		formElement.value?.reset()
		emit('updated')
	} catch (error) {
		if (isAxiosError(error) && error.response && error.response.data?.data?.message) {
			showError(error.response.data.data.message)
		}
		hasError.value = true
	} finally {
		isLoading.value = false
	}
}
</script>

<template>
	<form ref="form" @submit.prevent="onSubmit">
		<NcFormGroup
			:label="t('encryption', 'Update private key password')"
			:description="t('encryption', 'Your private encryption key could not be unlocked. If your login password has changed, try entering your previous login password to unlock your key, then your current password to update your key and restore access.')">
			<NcNoteCard v-if="recoveryEnabledForUser">
				{{ t('encryption', 'If you do not remember your old password you can ask your administrator to recover your files.') }}
			</NcNoteCard>

			<NcPasswordField
				v-model="oldPrivateKeyPassword"
				required
				name="oldPassword"
				:label="t('encryption', 'Previous login password')" />
			<NcPasswordField
				v-model="newPrivateKeyPassword"
				required
				name="newPassword"
				:label="t('encryption', 'Current login password')" />

			<NcButton
				type="submit"
				variant="primary">
				{{ t('encryption', 'Update') }}
			</NcButton>
		</NcFormGroup>
	</form>
</template>
