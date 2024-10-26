<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<template>
	<section class="fdow-section">
		<HeaderBar :input-id="inputId"
			:readable="propertyReadable" />

		<NcSelect :aria-label-listbox="t('settings', 'Day to use as the first day of week')"
			class="fdow-section__day-select"
			:clearable="false"
			:input-id="inputId"
			label="label"
			label-outside
			:options="dayOptions"
			:value="valueOption"
			@option:selected="updateFirstDayOfWeek" />
	</section>
</template>

<script lang="ts">
import HeaderBar from './shared/HeaderBar.vue'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import {
	ACCOUNT_SETTING_PROPERTY_ENUM,
	ACCOUNT_SETTING_PROPERTY_READABLE_ENUM,
} from '../../constants/AccountPropertyConstants'
import { getDayNames, getFirstDay } from '@nextcloud/l10n'
import { savePrimaryAccountProperty } from '../../service/PersonalInfo/PersonalInfoService'
import { handleError } from '../../utils/handlers.ts'
import { loadState } from '@nextcloud/initial-state'

interface DayOption {
	value: number,
	label: string,
}

const { firstDayOfWeek } = loadState<{firstDayOfWeek?: string}>(
	'settings',
	'personalInfoParameters',
	{},
)

export default {
	name: 'FirstDayOfWeekSection',
	components: {
		HeaderBar,
		NcSelect,
	},
	data() {
		let firstDay = -1
		if (firstDayOfWeek) {
			firstDay = parseInt(firstDayOfWeek)
		}

		return {
			firstDay,
		}
	},
	computed: {
		inputId(): string {
			return 'account-property-fdow'
		},
		propertyReadable(): string {
			return ACCOUNT_SETTING_PROPERTY_READABLE_ENUM.FIRST_DAY_OF_WEEK
		},
		dayOptions(): DayOption[] {
			const options = [{
				value: -1,
				label: t('settings', 'Derived from your locale ({weekDayName})', {
					weekDayName: getDayNames()[getFirstDay()],
				}),
			}]
			for (const [index, dayName] of getDayNames().entries()) {
				options.push({ value: index, label: dayName })
			}
			return options
		},
		valueOption(): DayOption | undefined {
			return this.dayOptions.find((option) => option.value === this.firstDay)
		},
	},
	methods: {
		async updateFirstDayOfWeek(option: DayOption): Promise<void> {
			try {
				const responseData = await savePrimaryAccountProperty(
					ACCOUNT_SETTING_PROPERTY_ENUM.FIRST_DAY_OF_WEEK,
					option.value.toString(),
				)
				this.handleResponse({
					value: option.value,
					status: responseData.ocs?.meta?.status,
				})
				window.location.reload()
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to update first day of week'),
					error: e,
				})
			}
		},

		handleResponse({ value, status, errorMessage, error }): void {
			if (status === 'ok') {
				this.firstDay = value
			} else {
				this.$emit('update:value', this.firstDay)
				handleError(error, errorMessage)
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.fdow-section {
	padding: 10px;

	&__day-select {
		width: 100%;
		margin-top: 6px; // align with other inputs
	}
}
</style>
