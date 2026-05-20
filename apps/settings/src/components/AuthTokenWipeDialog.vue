<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog
		:open="open"
		:name="t('settings', 'Wipe device?')"
		:buttons="buttons"
		size="normal"
		@update:open="onUpdateOpen">
		<NcNoteCard type="warning">
			<p>
				{{ t('settings', 'This will mark the device for remote wipe. The next time it connects, all synced data will be removed.') }}
			</p>
		</NcNoteCard>
		<p class="auth-token-wipe-dialog__body">
			{{ t('settings', 'Do you really want to wipe your data from "{name}"?', { name: token.name }) }}
		</p>
	</NcDialog>
</template>

<script lang="ts">
import type { IDialogButton } from '@nextcloud/dialogs'
import type { PropType } from 'vue'
import type { IToken } from '../store/authtoken.ts'

import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'

export default defineComponent({
	name: 'AuthTokenWipeDialog',

	components: {
		NcDialog,
		NcNoteCard,
	},

	props: {
		token: {
			type: Object as PropType<IToken>,
			required: true,
		},

		open: {
			type: Boolean,
			required: true,
		},
	},

	emits: {
		'update:open': (open: boolean) => typeof open === 'boolean',
		confirm: () => true,
	},

	computed: {
		buttons(): IDialogButton[] {
			return [
				{
					label: t('settings', 'Cancel'),
					// @ts-expect-error 'value' is missing from upstream types
					type: 'tertiary',
					callback: () => {
						this.$emit('update:open', false)
					},
				},
				{
					label: t('settings', 'Wipe device'),
					type: 'error',
					callback: () => {
						this.$emit('confirm')
						this.$emit('update:open', false)
					},
				},
			]
		},
	},

	methods: {
		t,
		onUpdateOpen(value: boolean): void {
			this.$emit('update:open', value)
		},
	},
})
</script>

<style lang="scss" scoped>
.auth-token-wipe-dialog__body {
	margin-block-start: calc(var(--default-grid-baseline) * 2);
}
</style>
