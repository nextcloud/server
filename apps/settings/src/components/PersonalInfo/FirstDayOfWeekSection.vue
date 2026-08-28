<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<template>
	<section class="fdow-section">
		<NcSelect
			:aria-label-listbox="t('settings', 'Day to use as the first day of week')"
			class="fdow-section__day-select"
			:clearable="false"
			:input-id="inputId"
			:input-label="t('settings', 'First day of week')"
			label="label"
			:options="dayOptions"
			:model-value="valueOption"
			@option:selected="updateFirstDayOfWeek" />
	</section>
</template>

<script lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { getDayNames } from '@nextcloud/l10n'
import moment from '@nextcloud/moment'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import {
	ACCOUNT_SETTING_PROPERTY_ENUM,
} from '../../constants/AccountPropertyConstants.ts'
import { savePrimaryAccountProperty } from '../../service/PersonalInfo/PersonalInfoService.js'
import { handleError } from '../../utils/handlers.ts'

interface DayOption {
	value: number
	label: string
}

const { firstDayOfWeek } = loadState<{ firstDayOfWeek?: string }>(
	'settings',
	'personalInfoParameters',
	{},
)

export default {
	name: 'FirstDayOfWeekSection',
	components: {
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

		dayOptions(): DayOption[] {
			const options = [{
				value: -1,
				label: t('settings', 'Derived from your locale ({weekDayName})', {
					// use the locale's default first day (not the user's saved override)
					weekDayName: getDayNames()[moment.localeData().firstDayOfWeek()],
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
	padding: 6px 0;

	&__day-select {
		width: 100%;
	}
}
</style>
