<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog
		:open="open"
		:name="t('settings', 'Confirm wipe')"
		:buttons="buttons"
		size="normal"
		@update:open="onUpdateOpen">
		<p>
			{{ t('settings', 'Do you really want to wipe your data from this device?') }}
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

export default defineComponent({
	name: 'AuthTokenWipeDialog',

	components: {
		NcDialog,
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
					variant: 'tertiary',
					callback: () => {
						this.$emit('update:open', false)
					},
				},
				{
					label: t('settings', 'Wipe device'),
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
