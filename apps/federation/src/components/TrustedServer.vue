<!--
  * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  * SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ITrustedServer } from '../services/api.ts'

import { mdiCheckNetworkOutline, mdiCloseNetworkOutline, mdiHelpNetworkOutline, mdiTrashCanOutline } from '@mdi/js'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { TrustedServerStatus } from '../services/api.ts'
import { deleteServer } from '../services/api.ts'
import { logger } from '../services/logger.ts'

const props = defineProps<{
	server: ITrustedServer
}>()

const emit = defineEmits<{
	delete: [ITrustedServer]
}>()

const isLoading = ref(false)

const hasError = computed(() => props.server.status === TrustedServerStatus.STATUS_FAILURE)
const serverIcon = computed(() => {
	switch (props.server.status) {
		case TrustedServerStatus.STATUS_OK:
			return mdiCheckNetworkOutline
		case TrustedServerStatus.STATUS_PENDING:
		case TrustedServerStatus.STATUS_ACCESS_REVOKED:
			return mdiHelpNetworkOutline
		case TrustedServerStatus.STATUS_FAILURE:
		default:
			return mdiCloseNetworkOutline
	}
})

const serverStatus = computed(() => {
	switch (props.server.status) {
		case TrustedServerStatus.STATUS_OK:
			return [t('federation', 'Server ok'), t('federation', 'User list was exchanged at least once successfully with the remote server.')]
		case TrustedServerStatus.STATUS_PENDING:
			return [t('federation', 'Server pending'), t('federation', 'Waiting for shared secret or initial user list exchange.')]
		case TrustedServerStatus.STATUS_ACCESS_REVOKED:
			return [t('federation', 'Server access revoked'), t('federation', 'Server access revoked')]
		case TrustedServerStatus.STATUS_FAILURE:
		default:
			return [t('federation', 'Server failure'), t('federation', 'Connection to the remote server failed or the remote server is misconfigured.')]
	}
})

/**
 * Emit delete event
 */
async function onDelete() {
	try {
		isLoading.value = true
		await deleteServer(props.server.id)
		emit('delete', props.server)
	} catch (error) {
		isLoading.value = false
		logger.error('Failed to delete trusted server', { error })
		showError(t('federation', 'Failed to delete trusted server. Please try again later.'))
	}
}
</script>

<template>
	<li :class="$style.trustedServer">
		<NcIconSvgWrapper
			:class="{
				[$style.trustedServer__icon_error]: hasError,
			}"
			:path="serverIcon"
			:name="serverStatus[0]"
			:title="serverStatus[1]" />

		<code :class="$style.trustedServer__url" v-text="server.url" />

		<NcButton
			:aria-label="t('federation', 'Delete')"
			:title="t('federation', 'Delete')"
			:disabled="isLoading"
			@click="onDelete">
			<template #icon>
				<NcLoadingIcon v-if="isLoading" />
				<NcIconSvgWrapper v-else :path="mdiTrashCanOutline" />
			</template>
		</NcButton>
	</li>
</template>

<style module>
.trustedServer {
	display: flex;
	flex-direction: row;
	gap: var(--default-grid-baseline);
	align-items: center;
	border-radius: var(--border-radius-element);
	padding-inline-start: var(--default-grid-baseline);
}

.trustedServer:hover {
	background-color: var(--color-background-hover);
}

.trustedServer__icon_error {
	color: var(--color-element-error);
}

.trustedServer__url {
	padding-inline: 1ch;
	flex: 1 0 auto;
}
</style>
