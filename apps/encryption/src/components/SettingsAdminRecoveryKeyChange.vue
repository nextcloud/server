<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { computed, ref, useTemplateRef } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcFormGroup from '@nextcloud/vue/components/NcFormGroup'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import { logger } from '../utils/logger.ts'

const formElement = useTemplateRef('form')

const isLoading = ref(false)
const hasError = ref(false)

const oldPassword = ref('')
const password = ref('')
const confirmPassword = ref('')
const passwordMatch = computed(() => password.value === confirmPassword.value)

/**
 * Handle the form submission to change the admin recovery key password
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
		await axios.post(
			generateUrl('/apps/encryption/ajax/changeRecoveryPassword'),
			{
				oldPassword: oldPassword.value,
				newPassword: password.value,
				confirmPassword: confirmPassword.value,
			},
		)
		oldPassword.value = password.value = confirmPassword.value = ''
		formElement.value?.reset()
	} catch (error) {
		hasError.value = true
		logger.error('Failed to update recovery key settings', { error })
	} finally {
		isLoading.value = false
	}
}
</script>

<template>
	<form ref="form" :class="$style.settingsAdminRecoveryKeyChange" @submit.prevent="onSubmit">
		<NcFormGroup
			:label="t('encryption', 'Change recovery key password')">
			<NcPasswordField
				v-model="oldPassword"
				required
				name="oldPassword"
				:label="t('encryption', 'Old recovery key password')" />
			<NcPasswordField
				v-model="password"
				required
				name="password"
				:label="t('encryption', 'New recovery key password')" />
			<NcPasswordField
				v-model="confirmPassword"
				required
				name="confirmPassword"
				:error="!passwordMatch && !!confirmPassword"
				:helper-text="(passwordMatch || !confirmPassword) ? '' : t('encryption', 'Passwords fields do not match')"
				:label="t('encryption', 'Repeat new recovery key password')" />

			<NcButton type="submit" variant="primary">
				{{ t('encryption', 'Change recovery key password') }}
			</NcButton>

			<NcNoteCard v-if="hasError" type="error">
				{{ t('encryption', 'An error occurred while changing the recovery key password. Please try again.') }}
			</NcNoteCard>
		</NcFormGroup>
	</form>
</template>

<style module>
.settingsAdminRecoveryKeyChange {
	margin-top: var(--clickable-area-small);
}
</style>
