<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import type { IAuthMechanism } from '../types.ts'

import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import ConfigurationEntry from '../components/AddExternalStorageDialog/ConfigurationEntry.vue'
import { ConfigurationFlag } from '../types.ts'
import logger from '../utils/logger.ts'

const modelValue = defineModel<Record<string, string | boolean>>({ required: true })

defineProps<{
	authMechanism: IAuthMechanism
}>()

const keySize = ref<number>()
watch(keySize, () => {
	if (keySize.value) {
		modelValue.value.private_key = ''
		modelValue.value.public_key = ''
	}
})

/**
 * Generates a new RSA key pair and fills the corresponding configuration entries.
 */
async function generateKeys() {
	try {
		// fallback to server-side key generation
		const { data } = await axios.post(generateUrl('/apps/files_external/ajax/public_key.php'), {
			keyLength: keySize.value,
		})

		modelValue.value.private_key = data.data.private_key
		modelValue.value.public_key = data.data.public_key
	} catch (error) {
		logger.error('Error generating RSA key pair', { error })
		showError(t('files_external', 'Error generating key pair'))
	}
}
</script>

<template>
	<div>
		<ConfigurationEntry
			v-for="configOption, configKey in authMechanism.configuration"
			v-show="!(configOption.flags & ConfigurationFlag.Hidden)"
			:key="configOption.value"
			v-model="modelValue[configKey]!"
			:configKey="configKey"
			:configOption="configOption" />

		<NcSelect
			v-model="keySize"
			:clearable="false"
			:inputLabel="t('files_external', 'Key size')"
			:options="[1024, 2048, 4096]"
			required />

		<NcButton
			:disabled="!keySize"
			wide
			@click="generateKeys">
			{{ t('files_external', 'Generate keys') }}
		</NcButton>
	</div>
</template>
