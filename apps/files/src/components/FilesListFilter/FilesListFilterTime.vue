<template>
	<FilesListFilter :is-active="isActive"
		:filter-name="label"
		@reset-filter="resetFilter">
		<template #icon>
			<NcIconSvgWrapper :path="mdiCalendarRange" />
		</template>
		<NcActionButton v-for="preset of timePresets"
			:key="preset.id"
			type="radio"
			close-after-click
			:model-value.sync="selectedOption"
			:value="preset.id">
			{{ preset.label }}
		</NcActionButton>
		<!-- TODO: Custom time range -->
	</FilesListFilter>
</template>

<script lang="ts">
import type { Node } from '@nextcloud/files'

import { mdiCalendarRange } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'

import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import FilesListFilter from './FilesListFilter.vue'

import useFilesFilter from '../../composables/useFilesFilter'

const startOfToday = () => (new Date()).setHours(0, 0, 0, 0)

const timePresets = [
	{
		id: 'today',
		label: t('files', 'Today'),
		filter: (time: number) => time > startOfToday(),
	},
	{
		id: 'last-7',
		label: t('files', 'Last 7 days'),
		filter: (time: number) => time > (startOfToday() - (7 * 24 * 60 * 60 * 1000)),
	},
	{
		id: 'last-30',
		label: t('files', 'Last 30 days'),
		filter: (time: number) => time > (startOfToday() - (30 * 24 * 60 * 60 * 1000)),
	},
	{
		id: 'this-year',
		label: t('files', 'This year ({year})', { year: (new Date()).getFullYear() }),
		filter: (time: number) => time > (new Date(startOfToday())).setMonth(0, 1),
	},
	{
		id: 'last-year',
		label: t('files', 'Last year ({year})', { year: (new Date()).getFullYear() - 1 }),
		filter: (time: number) => (time > (new Date(startOfToday())).setFullYear((new Date()).getFullYear() - 1, 0, 1)) && (time < (new Date(startOfToday())).setMonth(0, 1)),
	},
] as const

export default defineComponent({
	components: {
		FilesListFilter,
		NcActionButton,
		NcIconSvgWrapper,
	},

	props: {
	},

	setup() {
		return {
			...useFilesFilter(),
			timePresets,

			// icons used in template
			mdiCalendarRange,
		}
	},

	data() {
		return {
			selectedOption: null as (typeof timePresets)[number]['id'] | null,
			timeRangeEnd: null as number | null,
			timeRangeStart: null as number | null,
		}
	},

	computed: {
		/**
		 * Is the filter currently active
		 */
		isActive() {
			return this.selectedOption !== null
		},

		currentPreset() {
			return timePresets.find(({ id }) => id === this.selectedOption) ?? null
		},

		label() {
			if (this.currentPreset) {
				return this.currentPreset.label
			}
			return t('files', 'Modified')
		},
	},

	watch: {
		selectedOption() {
			if (this.selectedOption === null) {
				this.deleteFilter('files-filter-time')
			} else {
				const preset = this.currentPreset
				this.addFilter({
					id: 'files-filter-time',
					filter: (node: Node) => {
						if (!node.mtime) {
							return false
						}

						const mtime = node.mtime.getTime()
						if (preset) {
							return preset.filter(mtime)
						} else {
							return (!this.timeRangeStart || this.timeRangeStart < mtime) && (!this.timeRangeEnd || this.timeRangeEnd > mtime)
						}
					},
				})
			}
		},
	},

	methods: {
		t,

		resetFilter() {
			this.selectedOption = null
			this.timeRangeEnd = null
			this.timeRangeStart = null
		},
	},
})
</script>

<style scoped lang="scss">
.files-list-filter-time {
	&__clear-button :deep(.action-button__text) {
		color: var(--color-error-text);
	}
}
</style>
