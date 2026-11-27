<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="allowed-file-types-select">
		<NcSelect
			v-model="selectedOptions"
			:createOption="createExtensionOption"
			:disabled="disabled"
			:inputLabel="t('files_sharing', 'Allowed file types')"
			keepOpen
			multiple
			:options="options"
			:placeholder="t('files_sharing', 'Choose a file type or enter an extension like .pdf')"
			:selectable="(option: FileTypeOption) => isSelectableFileTypeOption(option, options)"
			taggable />
		<p class="allowed-file-types-select__hint">
			{{ t('files_sharing', 'Uploads are checked by their file name and extension, not by their content.') }}
		</p>
	</div>
</template>

<script setup lang="ts">
import type { FileTypeOption } from '../utils/allowedFileTypeOptions.ts'

import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import {
	createExtensionOption,
	getFileTypeOptions,
	isSelectableFileTypeOption,
	toFileTypeOptions,
} from '../utils/allowedFileTypeOptions.ts'

const allowedFileTypes = defineModel<string[]>({ required: true })

defineProps<{
	disabled?: boolean
}>()

const options = getFileTypeOptions()

const selectedOptions = computed({
	get: () => toFileTypeOptions(allowedFileTypes.value, options),
	set: (selected: FileTypeOption[]) => {
		allowedFileTypes.value = [...new Set(selected.map(({ id }) => id))]
	},
})
</script>

<style scoped lang="scss">
.allowed-file-types-select__hint {
	color: var(--color-text-maxcontrast);
	margin-block: calc(var(--default-grid-baseline) * 2) 0;
}
</style>
