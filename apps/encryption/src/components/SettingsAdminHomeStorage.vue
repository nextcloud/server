<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { watchDebounced } from '@vueuse/core'
import { ref, watch } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

const encryptHomeStorage = defineModel<boolean>({ required: true })
const isSavingHomeStorageEncryption = ref(false)

watch(encryptHomeStorage, () => {
	isSavingHomeStorageEncryption.value = true
})
watchDebounced(encryptHomeStorage, async (encryptHomeStorage, oldValue) => {
	if (encryptHomeStorage === oldValue) {
		// user changed their mind (likely quickly toggled), do nothing
		isSavingHomeStorageEncryption.value = false
		return
	}

	try {
		await axios.post(
			generateUrl('/apps/encryption/ajax/setEncryptHomeStorage'),
			{ encryptHomeStorage },
		)
	} finally {
		isSavingHomeStorageEncryption.value = false
	}
}, { debounce: 800 })
</script>

<template>
	<NcCheckboxRadioSwitch
		v-model="encryptHomeStorage"
		:loading="isSavingHomeStorageEncryption"
		:description="t('encryption', 'Enabling this option encrypts all files stored on the main storage, otherwise only files on external storage will be encrypted')"
		type="switch">
		{{ t('encryption', 'Encrypt the home storage') }}
	</NcCheckboxRadioSwitch>
</template>
