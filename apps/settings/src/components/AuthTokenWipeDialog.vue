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

defineProps<{
	/** The token to wipe. Kept for prop-shape parity with AuthTokenDeleteDialog. */
	token: IToken
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
		label: t('settings', 'Wipe device'),
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
		:name="t('settings', 'Confirm wipe')"
		:buttons="buttons"
		size="normal"
		@update:open="emit('update:open', $event)">
		<p>
			{{ t('settings', 'Do you really want to wipe your data from this device?') }}
		</p>
	</NcDialog>
</template>
