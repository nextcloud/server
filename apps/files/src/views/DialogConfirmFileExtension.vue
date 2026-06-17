<!--
 - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IDialogButton } from '@nextcloud/dialogs'

import svgIconCancel from '@mdi/svg/svg/cancel.svg?raw'
import svgIconCheck from '@mdi/svg/svg/check.svg?raw'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import { useUserConfigStore } from '../store/userconfig.ts'

const props = defineProps<{
	oldExtension?: string
	newExtension?: string
}>()

const emit = defineEmits<{
	(e: 'close', v: boolean): void
}>()

const userConfigStore = useUserConfigStore()
const dontShowAgain = computed({
	get: () => !userConfigStore.userConfig.show_dialog_file_extension,
	set: (value: boolean) => userConfigStore.update('show_dialog_file_extension', !value),
})

const buttons = computed(() => [
	{
		label: props.oldExtension
			? t('files', 'Keep {old}', { old: props.oldExtension })
			: t('files', 'Keep without extension'),
		icon: svgIconCancel,
		variant: 'secondary',
		callback: () => closeDialog(false),
	},
	{
		label: props.newExtension
			? t('files', 'Use {new}', { new: props.newExtension })
			: t('files', 'Remove extension'),
		icon: svgIconCheck,
		variant: 'primary',
		callback: () => closeDialog(true),
	},
] satisfies IDialogButton[])

/** Open state of the dialog */
const open = ref(true)

/**
 * Close the dialog and emit the response
 *
 * @param value User selected response
 */
function closeDialog(value: boolean) {
	emit('close', value)
	open.value = false
}
</script>

<template>
	<NcDialog
		:buttons="buttons"
		:open="open"
		no-close
		:name="t('files', 'Change file extension')"
		size="small">
		<p v-if="newExtension && oldExtension">
			{{ t('files', 'Changing the file extension from "{old}" to "{new}" may render the file unreadable.', { old: oldExtension, new: newExtension }) }}
		</p>
		<p v-else-if="oldExtension">
			{{ t('files', 'Removing the file extension "{old}" may render the file unreadable.', { old: oldExtension }) }}
		</p>
		<p v-else-if="newExtension">
			{{ t('files', 'Adding the file extension "{new}" may render the file unreadable.', { new: newExtension }) }}
		</p>

		<NcCheckboxRadioSwitch
			v-model="dontShowAgain"
			class="dialog-confirm-file-extension__checkbox"
			type="checkbox">
			{{ t('files', 'Do not show this dialog again.') }}
		</NcCheckboxRadioSwitch>
	</NcDialog>
</template>

<style scoped>
.dialog-confirm-file-extension__checkbox {
	margin-top: 1rem;
}
</style>
