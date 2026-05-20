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
		<NcNoteCard v-if="wiping" type="error">
			<p class="auth-token-delete-dialog__warning-headline">
				<strong>{{ t('settings', 'Remote wipe has not started yet.') }}</strong>
			</p>
			<p>
				{{ t('settings', 'Revoking now cancels the wipe. The device keeps its synced data.') }}
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
				? t('settings', 'Revoke and cancel pending wipe?')
				: t('settings', 'Revoke app password?')
		},

		bodyText(): string {
			return this.wiping
				? t('settings', 'Only continue if you no longer need the device to be wiped.')
				: t('settings', 'The app or device will lose access on its next sync. This cannot be undone.')
		},

		destructiveLabel(): string {
			return this.wiping
				? t('settings', 'Revoke and cancel wipe')
				: t('settings', 'Revoke')
		},

		buttons(): IDialogButton[] {
			return [
				{
					label: t('settings', 'Cancel'),
					variant: 'tertiary',
					callback: () => {
						this.$emit('update:open', false)
					},
				},
				{
					label: this.destructiveLabel,
					variant: 'error',
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
.auth-token-delete-dialog {
	&__warning-headline {
		margin-block-end: calc(var(--default-grid-baseline) / 2);
	}

	&__body {
		margin-block-start: calc(var(--default-grid-baseline) * 2);
	}
}
</style>
