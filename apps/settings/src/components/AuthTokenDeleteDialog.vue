<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IDialogButton } from '@nextcloud/dialogs'
import type { IToken } from '../store/authtoken.ts'

import { translate as t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import { TokenType } from '../store/authtoken.ts'

const props = defineProps<{
	/** The token being revoked */
	token: IToken
	/** Whether the dialog is open */
	open: boolean
}>()

const emit = defineEmits<{
	'update:open': [open: boolean]
	confirm: []
}>()

const wiping = computed(() => props.token.type === TokenType.WIPING_TOKEN)

const messages = computed(() => {
	if (wiping.value) {
		return {
			title: t('settings', 'Revoke and cancel pending wipe?'),
			body: t('settings', 'Only continue if you no longer need the device to be wiped.'),
			action: t('settings', 'Revoke and cancel wipe'),
		}
	}
	return {
		title: t('settings', 'Revoke app password?'),
		body: t('settings', 'The app or device will lose access on its next sync. This cannot be undone.'),
		action: t('settings', 'Revoke'),
	}
})

const buttons = computed<IDialogButton[]>(() => [
	{
		label: t('settings', 'Cancel'),
		variant: 'tertiary',
		callback: () => emit('update:open', false),
	},
	{
		label: messages.value.action,
		variant: 'error',
		callback: () => {
			emit('confirm')
			emit('update:open', false)
		},
	},
])
</script>

<template>
	<NcDialog :open="open"
		:name="messages.title"
		:buttons="buttons"
		size="normal"
		@update:open="emit('update:open', $event)">
		<NcNoteCard v-if="wiping"
			:heading="t('settings', 'Remote wipe has not started yet.')"
			type="error">
			{{ t('settings', 'Revoking now cancels the wipe. The device keeps its synced data.') }}
		</NcNoteCard>
		<p class="auth-token-delete-dialog__body">
			{{ messages.body }}
		</p>
	</NcDialog>
</template>

<style lang="scss" scoped>
.auth-token-delete-dialog__body {
	margin-block-start: calc(var(--default-grid-baseline) * 2);
}
</style>
