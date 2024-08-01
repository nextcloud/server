<!--
 - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcModal v-if="isModalOpen"
		id="unified-search"
		:name="t('core', 'Custom date range')"
		:show.sync="isModalOpen"
		:size="'small'"
		:clear-view-delay="0"
		:title="t('core', 'Custom date range')"
		@close="closeModal">
		<!-- Custom date range -->
		<div class="unified-search-custom-date-modal">
			<h1>{{ t('core', 'Custom date range') }}</h1>
			<div class="unified-search-custom-date-modal__pickers">
				<NcDateTimePicker :id="'unifiedsearch-custom-date-range-start'"
					v-model="dateFilter.startFrom"
					:label="t('core', 'Pick start date')"
					type="date" />
				<NcDateTimePicker :id="'unifiedsearch-custom-date-range-end'"
					v-model="dateFilter.endAt"
					:label="t('core', 'Pick end date')"
					type="date" />
			</div>
			<div class="unified-search-custom-date-modal__footer">
				<NcButton @click="applyCustomRange">
					{{ t('core', 'Search in date range') }}
					<template #icon>
						<CalendarRangeIcon :size="20" />
					</template>
				</NcButton>
			</div>
		</div>
	</NcModal>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcDateTimePicker from '@nextcloud/vue/dist/Components/NcDateTimePickerNative.js'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import CalendarRangeIcon from 'vue-material-design-icons/CalendarRange.vue'

export default {
	name: 'CustomDateRangeModal',
	components: {
		NcButton,
		NcModal,
		CalendarRangeIcon,
		NcDateTimePicker,
	},
	props: {
		isOpen: {
			type: Boolean,
			required: true,
		},
	},
	data() {
		return {
			dateFilter: { startFrom: null, endAt: null },
		}
	},
	computed: {
		isModalOpen: {
			get() {
				return this.isOpen
			},
			set(value) {
				this.$emit('update:is-open', value)
			},
		},
	},
	methods: {
		closeModal() {
			this.isModalOpen = false
		},
		applyCustomRange() {
			this.$emit('set:custom-date-range', this.dateFilter)
			this.closeModal()
		},
	},
}
</script>

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
