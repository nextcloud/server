<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { getCanonicalLocale, getDayNames, getFirstDay, t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import {
	ACCOUNT_SETTING_PROPERTY_ENUM,
} from '../../../constants/AccountPropertyConstants.ts'
import { savePrimaryAccountProperty } from '../../../service/PersonalInfo/PersonalInfoService.js'
import { handleError } from '../../../utils/handlers.ts'

interface WeekInfo {
	firstDay: number
}

interface DayOption {
	value: number
	label: string
}

const { firstDayOfWeek } = loadState<{ firstDayOfWeek?: string }>(
	'settings',
	'personalInfoParameters',
	{},
)

/**
 * The locale's first day of week, 0 being Sunday.
 * Without Intl week info (Firefox < 153) this is the day in use, which is wrong if the user
 * picked another one.
 */
function getLocaleFirstDay(): number {
	const locale = new Intl.Locale(getCanonicalLocale()) as Intl.Locale & { getWeekInfo?: () => WeekInfo, weekInfo?: WeekInfo }
	// Intl counts the days from 1 (Monday) to 7 (Sunday)
	return ((locale.getWeekInfo?.() ?? locale.weekInfo)?.firstDay ?? getFirstDay()) % 7
}

const inputId = 'account-property-fdow'

const firstDay = ref(firstDayOfWeek ? parseInt(firstDayOfWeek) : -1)

const dayOptions: DayOption[] = [{
	value: -1,
	label: t('settings', 'Derived from your locale ({weekDayName})', {
		// use the locale's default first day (not the user's saved override)
		weekDayName: getDayNames()[getLocaleFirstDay()],
	}),
}, ...getDayNames().map((dayName, index) => ({ value: index, label: dayName }))]

const valueOption = computed(() => dayOptions.find((option) => option.value === firstDay.value))

/**
 * @param option The day picked in the select, the page reloads with it
 */
async function updateFirstDayOfWeek(option: DayOption): Promise<void> {
	try {
		await savePrimaryAccountProperty(ACCOUNT_SETTING_PROPERTY_ENUM.FIRST_DAY_OF_WEEK, option.value.toString())
		firstDay.value = option.value
		window.location.reload()
	} catch (error) {
		handleError(error, t('settings', 'Unable to update first day of week'))
	}
}
</script>

<template>
	<section class="fdow-section">
		<NcSelect
			:aria-label-listbox="t('settings', 'Day to use as the first day of week')"
			class="fdow-section__day-select"
			:clearable="false"
			:inputId="inputId"
			:inputLabel="t('settings', 'First day of week')"
			label="label"
			:options="dayOptions"
			:modelValue="valueOption"
			@option:selected="updateFirstDayOfWeek" />
	</section>
</template>

<style lang="scss" scoped>
.fdow-section {
	padding: 6px 0;

	&__day-select {
		width: 100%;
	}
}
</style>
