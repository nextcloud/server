<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog
		:open="open"
		:name="dialogTitle"
		:buttons="buttons"
		size="normal"
		@update:open="onUpdateOpen">
		<NcNoteCard v-if="wiping" type="warning">
			<p>
				{{ t('settings', 'The remote wipe for this device has not finished yet. Revoking the app password now will cancel the pending wipe and the device will keep its access to previously synced data.') }}
			</p>
		</NcNoteCard>
		<p class="auth-token-delete-dialog__body">
			{{ bodyText }}
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
import { TokenType } from '../store/authtoken.ts'

export default defineComponent({
	name: 'AuthTokenDeleteDialog',

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
		wiping(): boolean {
			return this.token.type === TokenType.WIPING_TOKEN
		},

		dialogTitle(): string {
			return this.wiping
				? t('settings', 'Cancel pending remote wipe and revoke app password?')
				: t('settings', 'Revoke app password?')
		},

		bodyText(): string {
			if (this.wiping) {
				return t('settings', 'Continuing will cancel the pending remote wipe and permanently revoke this app password. The device will retain any data it has already synced.')
			}
			return t('settings', 'This will permanently revoke the app password. The connected app or device will lose access on its next sync.')
		},

		destructiveLabel(): string {
			return this.wiping
				? t('settings', 'Cancel wipe and revoke')
				: t('settings', 'Revoke')
		},

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
					label: this.destructiveLabel,
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
.auth-token-delete-dialog__body {
	margin-block-start: calc(var(--default-grid-baseline) * 2);
}
</style>
