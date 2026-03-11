<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<NcButton
			v-for="preset of timePresets"
			:key="preset.id"
			alignment="start"
			:pressed="preset === selectedOption"
			variant="tertiary"
			wide
			@update:pressed="$event ? (selectedOption = preset) : onReset()">
			{{ preset.label }}
		</NcButton>
		<NcDateTimePicker
			v-if="selectedOption?.id === 'custom'"
			v-model="timeRange"
			append-to-body
			:aria-label="t('files', 'Custom date range')"
			type="date-range" />
	</div>
</template>

<script setup lang="ts">
import type { ITimePreset, ModifiedFilter } from '../../filters/ModifiedFilter.ts'

import { t } from '@nextcloud/l10n'
import { NcDateTimePicker } from '@nextcloud/vue'
import { onMounted, onUnmounted, ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'

const props = defineProps<{
	filter: ModifiedFilter
}>()

const selectedOption = ref<typeof timePresets[number]>()
watch(selectedOption, (preset) => {
	if (selectedOption.value) {
		if (selectedOption.value.id === 'custom' && !timeRange.value) {
			timeRange.value = [new Date(startOfLastWeek()), new Date(startOfToday())]
			selectedOption.value.timeRange = [...timeRange.value]
		}
		props.filter.setPreset(selectedOption.value)
	} else {
		props.filter.setPreset()
	}
})

const timeRange = ref<[Date, Date]>()
watch(timeRange, () => {
	if (timeRange.value) {
		selectedOption.value!.timeRange = [...timeRange.value]
		props.filter.setPreset(selectedOption.value)
	}
})

onMounted(() => {
	selectedOption.value = props.filter.preset && timePresets.find((f) => f.id === props.filter.preset!.id)
	props.filter.addEventListener('reset', onReset)
})
onUnmounted(() => {
	props.filter.removeEventListener('reset', onReset)
})

/**
 * Handler for resetting the filter
 */
function onReset() {
	selectedOption.value = undefined
	timeRange.value = undefined
}
</script>

<script lang="ts">
const startOfToday = () => (new Date()).setHours(0, 0, 0, 0)
const startOfLastWeek = () => startOfToday() - (7 * 24 * 60 * 60 * 1000)

/**
 * Available presets
 */
const timePresets = [
	{
		id: 'today',
		label: t('files', 'Today'),
		filter: (time: number) => time > startOfToday(),
	} satisfies ITimePreset,
	{
		id: 'last-7',
		label: t('files', 'Last 7 days'),
		filter: (time: number) => time > startOfLastWeek(),
	} satisfies ITimePreset,
	{
		id: 'last-30',
		label: t('files', 'Last 30 days'),
		filter: (time: number) => time > (startOfToday() - (30 * 24 * 60 * 60 * 1000)),
	} satisfies ITimePreset,
	{
		id: 'this-year',
		label: t('files', 'This year ({year})', { year: (new Date()).getFullYear() }),
		filter: (time: number) => time > (new Date(startOfToday())).setMonth(0, 1),
	} satisfies ITimePreset,
	{
		id: 'last-year',
		label: t('files', 'Last year ({year})', { year: (new Date()).getFullYear() - 1 }),
		filter: (time: number) => (time > (new Date(startOfToday())).setFullYear((new Date()).getFullYear() - 1, 0, 1)) && (time < (new Date(startOfToday())).setMonth(0, 1)),
	} satisfies ITimePreset,
	{
		id: 'custom',
		label: t('files', 'Custom range'),
		timeRange: [new Date(startOfLastWeek()), new Date(startOfToday())],
		filter(time: number) {
			if (!this.timeRange) {
				return true
			}
			const timeValue = new Date(time).getTime()
			return timeValue >= this.timeRange[0].getTime() && timeValue <= this.timeRange[1].getTime()
		},
	} satisfies ITimePreset & Record<string, unknown>,
]
</script>

<style scoped lang="scss">
.files-list-filter-time {
	&__clear-button :deep(.action-button__text) {
		color: var(--color-error-text);
	}
}
</style>
