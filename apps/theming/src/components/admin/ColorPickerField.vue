<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { AdminThemingParameters } from '../../types.d.ts'

import { mdiPaletteOutline, mdiUndo } from '@mdi/js'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { computed, ref, toRef, useId, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcColorPicker from '@nextcloud/vue/components/NcColorPicker'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { useAdminThemingValue } from '../../composables/useAdminThemingValue.js'
import { getTextColor } from '../../utils/color.ts'

const props = defineProps<{
	name: keyof AdminThemingParameters
	label: string
	defaultValue: string
}>()

const emit = defineEmits<{
	updated: []
}>()

const id = useId()

const modelValue = ref(loadState<AdminThemingParameters>('theming', 'adminThemingParameters')[props.name] as string)
const previewColor = ref(modelValue.value)
watch(modelValue, (v) => {
	previewColor.value = v
})

const {
	isSaving,
	reset,
} = useAdminThemingValue(() => props.name, modelValue, toRef(props, 'defaultValue'))
watch(isSaving, (v) => !v && emit('updated'))

const textColor = computed(() => getTextColor(previewColor.value))
</script>

<template>
	<div :class="$style.colorPickerField">
		<div :class="$style.colorPickerField__row">
			<NcColorPicker
				:id
				v-model="previewColor"
				advanced-fields
				@submit="modelValue = $event!">
				<NcButton
					:class="$style.colorPickerField__button"
					size="large"
					variant="primary"
					:style="{
						'--color-primary-element': previewColor,
						'--color-primary-element-text': textColor,
						'--color-primary-element-hover': 'color-mix(in srgb, var(--color-primary-element) 70%, var(--color-primary-element-text))',
					}">
					<template #icon>
						<NcLoadingIcon v-if="isSaving" :appearance="textColor === '#ffffff' ? 'light' : 'dark'" />
						<NcIconSvgWrapper v-else :path="mdiPaletteOutline" />
					</template>
					{{ label }}
				</NcButton>
			</NcColorPicker>
			<NcButton
				v-if="modelValue !== defaultValue"
				variant="tertiary"
				:aria-label="t('theming', 'Reset to default')"
				:title="t('theming', 'Reset to default')"
				@click="reset">
				<template #icon>
					<NcIconSvgWrapper :path="mdiUndo" />
				</template>
			</NcButton>
		</div>
		<p :class="$style.colorPickerField__description">
			<slot name="description" />
		</p>
	</div>
</template>

<style module>
.colorPickerField {
	display: flex;
	flex-direction: column;
}

.colorPickerField__row {
	display: flex;
	flex-direction: row;
	align-items: center;
	gap: calc(1.5 * var(--default-grid-baseline));
}

.colorPickerField__button {
	min-width: clamp(200px, 25vw, 300px) !important;
}

.colorPickerField__description {
	color: var(--color-text-maxcontrast);
	margin-block: calc(0.5 * var(--default-grid-baseline)) var(--default-grid-baseline);
}

.colorPickerField__description:empty {
	display: none;
}
</style>
