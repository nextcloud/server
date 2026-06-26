<!--
  * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  * SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ITrustedServer } from '../services/api.ts'

import { mdiPlus } from '@mdi/js'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { nextTick, ref, useTemplateRef } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { addServer, ApiError } from '../services/api.ts'
import { logger } from '../services/logger.ts'

const emit = defineEmits<{
	add: [server: ITrustedServer]
}>()

const formElement = useTemplateRef<HTMLFormElement>('form')
const newServerUrl = ref('')

/**
 * Handle add trusted server form submission
 */
async function onAdd() {
	try {
		const server = await addServer(newServerUrl.value)
		newServerUrl.value = ''
		emit('add', server)

		nextTick(() => formElement.value?.reset()) // Reset native form validation state
		showSuccess(t('federation', 'Added to the list of trusted servers'))
	} catch (error) {
		logger.error('Failed to add trusted server', { error })
		if (error instanceof ApiError) {
			showError(error.message)
		} else {
			showError(t('federation', 'Could not add trusted server. Please try again later.'))
		}
	}
}
</script>

<template>
	<form ref="form" @submit.prevent="onAdd">
		<h3 :class="$style.addTrustedServerForm__heading">
			{{ t('federation', 'Add trusted server') }}
		</h3>
		<div :class="$style.addTrustedServerForm__wrapper">
			<NcTextField
				v-model="newServerUrl"
				:label="t('federation', 'Server url')"
				placeholder="https://…"
				required
				type="url" />
			<NcButton
				:class="$style.addTrustedServerForm__submitButton"
				:aria-label="t('federation', 'Add')"
				:title="t('federation', 'Add')"
				type="submit"
				variant="primary">
				<template #icon>
					<NcIconSvgWrapper :path="mdiPlus" />
				</template>
			</NcButton>
		</div>
	</form>
</template>

<style module>
.addTrustedServerForm__heading {
	font-size: 1.2rem;
	margin-block: 0.5lh 0.25lh;
}

.addTrustedServerForm__wrapper {
	display: flex;
	gap: var(--default-grid-baseline);
	align-items: end;
	max-width: 600px;
}

.addTrustedServerForm__submitButton {
	max-height: var(--default-clickable-area);
}
</style>
