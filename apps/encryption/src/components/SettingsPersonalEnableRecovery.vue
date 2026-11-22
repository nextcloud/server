<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import axios, { isAxiosError } from '@nextcloud/axios'
import { showError, showLoading } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { watchDebounced } from '@vueuse/core'
import { ref, watch } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

const userEnableRecovery = defineModel<boolean>({ required: true })
const isLoading = ref(false)

watch(userEnableRecovery, () => {
	isLoading.value = true
})
watchDebounced([userEnableRecovery], async ([newValue], [oldValue]) => {
	if (newValue === oldValue) {
		// user changed their mind (likely quickly toggled), do nothing
		isLoading.value = false
		return
	}

	const toast = showLoading(t('encryption', 'Updating recovery keys. This can take some time…'))
	try {
		await axios.post(
			generateUrl('/apps/encryption/ajax/userSetRecovery'),
			{ userEnableRecovery: userEnableRecovery.value },
		)
	} catch (error) {
		userEnableRecovery.value = oldValue
		if (isAxiosError(error) && error.response && error.response.data?.data?.message) {
			showError(error.response.data.data.message)
		}
	} finally {
		toast.hideToast()
		isLoading.value = false
	}
}, { debounce: 800 })
</script>

<template>
	<NcCheckboxRadioSwitch
		v-model="userEnableRecovery"
		type="switch"
		:loading="isLoading"
		:description="t('encryption', 'Controls whether the administrator can use the recovery key to restore access to your encrypted files if you lose your login password.')">
		{{ t('encryption', 'Allow recovery of my encrypted files') }}
	</NcCheckboxRadioSwitch>
</template>
