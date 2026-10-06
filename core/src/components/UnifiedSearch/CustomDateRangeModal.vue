<!--
 - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import { mdiCalendarRange } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDateTimePicker from '@nextcloud/vue/components/NcDateTimePickerNative'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcModal from '@nextcloud/vue/components/NcModal'

/** The picked range, either end may still be missing */
export interface DateRange {
	startFrom: Date | null
	endAt: Date | null
}

const isOpen = defineModel<boolean>('isOpen', { required: true })

const emit = defineEmits<{
	setCustomDateRange: [range: DateRange]
}>()

const dateFilter = ref<DateRange>({ startFrom: null, endAt: null })

/**
 * Search in the picked range.
 */
function applyCustomRange() {
	emit('setCustomDateRange', dateFilter.value)
	isOpen.value = false
}
</script>

<template>
	<NcModal
		v-if="isOpen"
		id="unified-search"
		v-model:show="isOpen"
		:name="t('core', 'Custom date range')"
		size="small">
		<!-- Custom date range -->
		<div class="unified-search-custom-date-modal">
			<h1>{{ t('core', 'Custom date range') }}</h1>
			<div class="unified-search-custom-date-modal__pickers">
				<NcDateTimePicker
					id="unifiedsearch-custom-date-range-start"
					v-model="dateFilter.startFrom"
					:label="t('core', 'Pick start date')"
					type="date" />
				<NcDateTimePicker
					id="unifiedsearch-custom-date-range-end"
					v-model="dateFilter.endAt"
					:label="t('core', 'Pick end date')"
					type="date" />
			</div>
			<div class="unified-search-custom-date-modal__footer">
				<NcButton @click="applyCustomRange">
					{{ t('core', 'Search in date range') }}
					<template #icon>
						<NcIconSvgWrapper :path="mdiCalendarRange" />
					</template>
				</NcButton>
			</div>
		</div>
	</NcModal>
</template>

<style lang="scss" scoped>
.unified-search-custom-date-modal {
	padding: 10px 20px 10px 20px;

	h1 {
		font-size: 16px;
		font-weight: bolder;
		line-height: 2em;
	}

	&__pickers {
		display: flex;
		flex-direction: column;
	}

	&__footer {
		display: flex;
		justify-content: end;
	}

}
</style>
