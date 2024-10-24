<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<NcDialog is-form
		:name="label"
		:open.sync="open"
		@submit="createFederatedShare">
		<NcTextField ref="input"
			:label="t('core', 'Federated user')"
			:placeholder="t('core', 'user@your-nextcloud.org')"
			required
			:value.sync="remoteUrl" />
		<template #actions>
			<NcButton :disabled="loading" type="primary" native-type="submit">
				<template v-if="loading" #icon>
					<NcLoadingIcon />
				</template>
				{{ t('core', 'Create share') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script setup lang="ts">
import type Vue from 'vue'

import { t } from '@nextcloud/l10n'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { getSharingToken } from '@nextcloud/sharing/public'
import { nextTick, onMounted, ref, watch } from 'vue'
import axios from '@nextcloud/axios'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import logger from '../../logger'

defineProps<{
	label: string
}>()

const loading = ref(false)
const remoteUrl = ref('')
// Todo: @nextcloud/vue should expose the types correctly
const input = ref<Vue & { focus: () => void }>()
const open = ref(true)

// Focus when mounted
onMounted(() => nextTick(() => input.value!.focus()))

// Check validity
watch(remoteUrl, () => {
	let validity = ''
	if (!remoteUrl.value.includes('@')) {
		validity = t('core', 'The remote URL must include the user.')
	} else if (!remoteUrl.value.match(/@(.+\..{2,}|localhost)(:\d\d+)?$/)) {
		validity = t('core', 'Invalid remote URL.')
	}
	input.value!.$el.querySelector('input')!.setCustomValidity(validity)
	input.value!.$el.querySelector('input')!.reportValidity()
})

/**
 * Create a federated share for the current share
 */
async function createFederatedShare() {
	loading.value = true

	try {
		const url = generateUrl('/apps/federatedfilesharing/createFederatedShare')
		const { data } = await axios.post<{ remoteUrl: string }>(url, {
			shareWith: remoteUrl.value,
			token: getSharingToken(),
		})
		if (data.remoteUrl.includes('://')) {
			window.location.href = data.remoteUrl
		} else {
			window.location.href = `${window.location.protocol}//${data.remoteUrl}`
		}
	} catch (error) {
		logger.error('Failed to create federated share', { error })
		showError(t('files_sharing', 'Failed to add the public link to your Nextcloud'))
	} finally {
		loading.value = false
	}
}
</script>
