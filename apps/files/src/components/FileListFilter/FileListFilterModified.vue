<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<FileListFilter :is-active="isActive"
		:filter-name="t('files', 'Modified')"
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
	</FileListFilter>
</template>

<script lang="ts">
import type { PropType } from 'vue'
import type { ITimePreset } from '../../filters/ModifiedFilter.ts'

import { mdiCalendarRange } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'

import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import FileListFilter from './FileListFilter.vue'

export default defineComponent({
	components: {
		FileListFilter,
		NcActionButton,
		NcIconSvgWrapper,
	},

	props: {
		timePresets: {
			type: Array as PropType<ITimePreset[]>,
			required: true,
		},
	},

	setup() {
		return {
			// icons used in template
			mdiCalendarRange,
		}
	},

	data() {
		return {
			selectedOption: null as string | null,
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
			return this.timePresets.find(({ id }) => id === this.selectedOption) ?? null
		},
	},

	watch: {
		selectedOption() {
			if (this.selectedOption === null) {
				this.$emit('update:preset')
			} else {
				const preset = this.currentPreset
				this.$emit('update:preset', preset)
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
