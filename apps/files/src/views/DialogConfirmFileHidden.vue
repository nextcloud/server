<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import { useUserConfigStore } from '../store/userconfig.ts'

const props = defineProps<{
	filename: string
}>()

const emit = defineEmits<{
	(e: 'close', v: boolean): void
}>()

const userConfigStore = useUserConfigStore()
const dontShowAgain = computed({
	get: () => !userConfigStore.userConfig.show_dialog_file_extension,
	set: (value: boolean) => userConfigStore.update('show_dialog_file_extension', !value),
})

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
		no-close
		:open="open"
		:name="t('files', 'Rename file to hidden')"
		size="small">
		<div>
			<p>
				{{ t('files', 'Prefixing a filename with a dot may render the file hidden.') }}
				{{ t('files', 'Are you sure you want to rename the file to "{filename}"?', { filename: props.filename }) }}
			</p>

			<NcCheckboxRadioSwitch
				v-model="dontShowAgain"
				:class="$style.dialogConfirmFileHidden__checkbox"
				type="switch">
				{{ t('files', 'Do not show this dialog again.') }}
			</NcCheckboxRadioSwitch>
		</div>
		<template #actions>
			<NcButton variant="secondary" @click="closeDialog(false)">
				{{ t('files', 'Cancel') }}
			</NcButton>
			<NcButton variant="primary" @click="closeDialog(true)">
				{{ t('files', 'Rename') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<style module>
.dialogConfirmFileHidden__checkbox {
	margin-top: 1rem;
}
</style>
