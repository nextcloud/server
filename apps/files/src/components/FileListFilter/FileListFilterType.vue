<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div :class="$style.fileListFilterType">
		<NcButton
			v-for="fileType of typePresets"
			:key="fileType.id"
			:pressed="selectedOptions.includes(fileType)"
			variant="tertiary"
			alignment="start"
			wide
			@update:pressed="toggleOption(fileType, $event)">
			<template #icon>
				<NcIconSvgWrapper :svg="fileType.icon" />
			</template>
			{{ fileType.label }}
		</NcButton>
	</div>
</template>

<script setup lang="ts">
import type { ITypePreset, TypeFilter } from '../../filters/TypeFilter.ts'

import svgDocument from '@mdi/svg/svg/file-document.svg?raw'
import svgPDF from '@mdi/svg/svg/file-pdf-box.svg?raw'
import svgPresentation from '@mdi/svg/svg/file-presentation-box.svg?raw'
import svgSpreadsheet from '@mdi/svg/svg/file-table-box.svg?raw'
import svgFolder from '@mdi/svg/svg/folder.svg?raw'
import svgImage from '@mdi/svg/svg/image.svg?raw'
import svgMovie from '@mdi/svg/svg/movie.svg?raw'
import svgAudio from '@mdi/svg/svg/music.svg?raw'
import { t } from '@nextcloud/l10n'
import { onMounted, onUnmounted, ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

const props = defineProps<{
	filter: TypeFilter
}>()

const selectedOptions = ref<ITypePreset[]>([])
watch(selectedOptions, () => {
	props.filter.setPresets([...selectedOptions.value])
})

onMounted(() => {
	props.filter.addEventListener('reset', resetFilter)
	props.filter.addEventListener('deselect', onDeselect)
	selectedOptions.value = typePresets.filter(({ id }) => props.filter.presets.some((preset) => preset.id === id))
})
onUnmounted(() => {
	props.filter.removeEventListener('reset', resetFilter)
	props.filter.removeEventListener('deselect', onDeselect)
})

/**
 * Handler for reset event from filter
 */
function resetFilter() {
	selectedOptions.value = []
}

/**
 * Handle deselect event from filter
 *
 * @param event - The custom event
 */
function onDeselect(event: CustomEvent<string>) {
	const option = typePresets.find((preset) => preset.id === event.detail)
	if (option) {
		toggleOption(option, false)
	}
}

/**
 * Toggle option from selected option
 *
 * @param option The option to toggle
 * @param selected Whether the option is selected or not
 */
function toggleOption(option: ITypePreset, selected: boolean) {
	selectedOptions.value = selectedOptions.value.filter((o) => o.id !== option.id)

	if (selected) {
		selectedOptions.value.push(option)
	}
}
</script>

<script lang="ts">
/**
 * Available presets
 */
const typePresets = [
	{
		id: 'document',
		label: t('files', 'Documents'),
		icon: colorize(svgDocument, '#49abea'),
		mime: ['x-office/document'],
	},
	{
		id: 'spreadsheet',
		label: t('files', 'Spreadsheets'),
		icon: colorize(svgSpreadsheet, '#9abd4e'),
		mime: ['x-office/spreadsheet'],
	},
	{
		id: 'presentation',
		label: t('files', 'Presentations'),
		icon: colorize(svgPresentation, '#f0965f'),
		mime: ['x-office/presentation'],
	},
	{
		id: 'pdf',
		label: t('files', 'PDFs'),
		icon: colorize(svgPDF, '#dc5047'),
		mime: ['application/pdf'],
	},
	{
		id: 'folder',
		label: t('files', 'Folders'),
		icon: colorize(svgFolder, window.getComputedStyle(document.body).getPropertyValue('--color-primary-element')),
		mime: ['httpd/unix-directory'],
	},
	{
		id: 'audio',
		label: t('files', 'Audio'),
		icon: svgAudio,
		mime: ['audio'],
	},
	{
		id: 'image',
		// TRANSLATORS: This is for filtering files, e.g. PNG or JPEG, so photos, drawings, or images in general
		label: t('files', 'Images'),
		icon: svgImage,
		mime: ['image'],
	},
	{
		id: 'video',
		label: t('files', 'Videos'),
		icon: svgMovie,
		mime: ['video'],
	},
] as ITypePreset[]

/**
 * Helper to colorize an svg icon
 *
 * @param svg - the svg content
 * @param color - the color to apply
 */
function colorize(svg: string, color: string) {
	return svg.replace('<path ', `<path fill="${color}" `)
}
</script>

<style module>
.fileListFilterType {
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);
	width: 100%;
}
</style>
