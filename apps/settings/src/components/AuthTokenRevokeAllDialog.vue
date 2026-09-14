<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IDialogButton } from '@nextcloud/dialogs'

import { translatePlural as n, translate as t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'

defineProps<{
	/** Number of sessions and app passwords that will be revoked */
	count: number
	/** Number of devices keeping access because their remote wipe is still pending */
	wipePendingCount: number
	/** Whether the dialog is open */
	open: boolean
}>()

const emit = defineEmits<{
	'update:open': [open: boolean]
	confirm: []
}>()

const buttons = computed<IDialogButton[]>(() => [
	{
		label: t('settings', 'Cancel'),
		variant: 'tertiary',
		callback: () => emit('update:open', false),
	},
	{
		label: t('settings', 'Revoke all others'),
		variant: 'error',
		callback: () => {
			emit('confirm')
			emit('update:open', false)
		},
	},
])
</script>

<template>
	<NcDialog
		:open="open"
		:name="t('settings', 'Revoke all other sessions?')"
		:buttons="buttons"
		size="normal"
		@update:open="emit('update:open', $event)">
		<NcNoteCard v-if="wipePendingCount > 0" type="warning">
			{{ n('settings',
				'%n device keeps access because its remote wipe has not finished. Revoke it on its own to cancel the wipe.',
				'%n devices keep access because their remote wipe has not finished. Revoke them on their own to cancel the wipe.',
				wipePendingCount) }}
		</NcNoteCard>
		<p class="auth-token-revoke-all-dialog__body">
			{{ n('settings',
				'This signs out %n other device or app. You stay signed in here.',
				'This signs out %n other devices and apps. You stay signed in here.',
				count) }}
		</p>
		<p class="auth-token-revoke-all-dialog__body">
			{{ t('settings', 'Sync clients and connected services have to sign in again. This cannot be undone.') }}
		</p>
	</NcDialog>
</template>

<style lang="scss" scoped>
.auth-token-revoke-all-dialog__body {
	margin-block-start: calc(var(--default-grid-baseline) * 2);
}
</style>
