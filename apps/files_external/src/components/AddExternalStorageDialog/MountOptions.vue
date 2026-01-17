<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import type { IMountOptions } from '../../types.ts'

import { mdiChevronDown, mdiChevronRight } from '@mdi/js'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { computed, ref, useId, watchEffect } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { MountOptionsCheckFilesystem } from '../../types.ts'

const mountOptions = defineModel<Partial<IMountOptions>>({ required: true })
watchEffect(() => {
	if (Object.keys(mountOptions.value).length === 0) {
		mountOptions.value.encrypt = true
		mountOptions.value.previews = true
		mountOptions.value.enable_sharing = false
		mountOptions.value.filesystem_check_changes = MountOptionsCheckFilesystem.OncePerRequest
		mountOptions.value.encoding_compatibility = false
		mountOptions.value.readonly = false
	}
})

const { hasEncryption } = loadState<{ hasEncryption: boolean }>('files_external', 'settings')

const idButton = useId()
const idFieldset = useId()

const isExpanded = ref(false)

const checkFilesystemOptions = [
	{
		label: t('files_external', 'Never'),
		value: MountOptionsCheckFilesystem.Never,
	},
	{
		label: t('files_external', 'Once every direct access'),
		value: MountOptionsCheckFilesystem.OncePerRequest,
	},
	{
		label: t('files_external', 'Always'),
		value: MountOptionsCheckFilesystem.Always,
	},
]
const checkFilesystem = computed({
	get() {
		return checkFilesystemOptions.find((option) => option.value === mountOptions.value.filesystem_check_changes)
	},
	set(value) {
		mountOptions.value.filesystem_check_changes = value?.value ?? MountOptionsCheckFilesystem.OncePerRequest
	},
})

</script>

<template>
	<div :class="$style.mountOptions">
		<NcButton
			:id="idButton"
			:aria-controls="idFieldset"
			:aria-expanded="isExpanded"
			variant="tertiary-no-background"
			@click="isExpanded = !isExpanded">
			<template #icon>
				<NcIconSvgWrapper directional :path="isExpanded ? mdiChevronDown : mdiChevronRight" />
			</template>
			{{ t('files_external', 'Mount options') }}
		</NcButton>

		<fieldset
			v-show="isExpanded"
			:id="idFieldset"
			:class="$style.mountOptions__fieldset"
			:aria-labelledby="idButton">
			<NcSelect
				v-model="checkFilesystem"
				:input-label="t('files_external', 'Check filesystem changes')"
				:options="checkFilesystemOptions" />

			<NcCheckboxRadioSwitch v-model="modelValue.readonly" type="switch">
				{{ t('files_external', 'Read only') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch v-model="modelValue.previews" type="switch">
				{{ t('files_external', 'Enable previews') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch v-model="modelValue.enable_sharing" type="switch">
				{{ t('files_external', 'Enable sharing') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch v-if="hasEncryption" v-model="modelValue.encrypt" type="switch">
				{{ t('files_external', 'Enable encryption') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch v-model="modelValue.encoding_compatibility" type="switch">
				{{ t('files_external', 'Compatibility with Mac NFD encoding (slow)') }}
			</NcCheckboxRadioSwitch>
		</fieldset>
	</div>
</template>

<style module>
.mountOptions {
	background-color: hsl(from var(--color-primary-element-light) h s calc(l * 1.045));
	border-radius: var(--border-radius-element);

	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);
	width: 100%;
}

.mountOptions__fieldset {
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);
	padding-inline: calc(2 * var(--default-grid-baseline)) var(--default-grid-baseline);
}
</style>
