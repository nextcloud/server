<!--
 - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAccountProperty } from '../../../constants/AccountPropertyConstants.ts'

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { useDebounceFn } from '@vueuse/core'
import { computed, ref, useTemplateRef } from 'vue'
import NcDateTimePickerNative from '@nextcloud/vue/components/NcDateTimePickerNative'
import VisibilityScopeControl from './shared/VisibilityScopeControl.vue'
import { NAME_READABLE_ENUM, SCOPE_ENUM } from '../../../constants/AccountPropertyConstants.ts'
import { savePrimaryAccountProperty } from '../../../service/PersonalInfo/PersonalInfoService.js'
import { handleError } from '../../../utils/handlers.ts'

const { birthdate } = loadState<{ birthdate: IAccountProperty }>('settings', 'personalInfoParameters')
const readable = NAME_READABLE_ENUM[birthdate.name]

// new Date(1, 0, 1) would map the year to 1901
const minDate = new Date(0)
minDate.setFullYear(1, 0, 1)
const maxDate = new Date(9999, 11, 31)

const picker = useTemplateRef('picker')

const scope = ref(birthdate.scope ?? SCOPE_ENUM.PRIVATE)
const value = ref(birthdate.value)
let savedValue = birthdate.value

/**
 * Adjusted value for usage with `NcDateTimePickerNative` (internally `<input="date">`)
 * The saved value is is UTC and we want to show it the same regardless of the browsers/OSs timezone.
 * When the adjusted value is displayed and the users timezone is applied, this adjusted value then looks like the UTC value.
 */
const timezoneAdjustedValue = computed(() => {
	// example: value === '1987-12-01T00:00:00.000Z' or '1987-12-01'
	const date = value.value ? new Date(value.value) : null
	if (date === null || Number.isNaN(date.getTime())) {
		return null
	}

	// example: Mon Nov 30 1987 16:00:00 GMT-0800 (Pacific Standard Time)
	// `NcDateTimePickerNative` would show this as 11/30/1987
	const timezoneOffsetMilliseconds = date.getTimezoneOffset() * 60 * 1000

	// example: Tue Dec 01 1987 00:00:00 GMT-0800 (Pacific Standard Time)
	// `NcDateTimePickerNative` will show this as 12/01/1987
	return new Date(date.getTime() + timezoneOffsetMilliseconds)
})

const debouncePropertyChange = useDebounceFn(async (date: Date | null) => {
	if (date === null) {
		value.value = ''
		await updateProperty('')
		return
	}
	const day = date.getDate().toString().padStart(2, '0')
	const month = (date.getMonth() + 1).toString().padStart(2, '0')
	const year = date.getFullYear().toString().padStart(4, '0')
	value.value = `${year}-${month}-${day}`
	await updateProperty(value.value)
}, 500)

/**
 * @param date The picked date, null while the input is empty or only partially filled
 */
function onInput(date: Date | null) {
	if (date === null) {
		// a partially filled input is empty as well, but flagged as bad input
		const input = picker.value?.$el.querySelector('input')
		if (savedValue && input?.value === '' && !input.validity.badInput) {
			debouncePropertyChange(null)
		}
		return
	}
	// the browser clamps overflowing year segments (e.g. 9999999) to 275760-09-13
	if (!(date instanceof Date) || date.getFullYear() < 1 || date.getFullYear() > 9999) {
		return
	}
	debouncePropertyChange(date)
}

/**
 * @param newValue The date to store, e.g. "1987-12-01", empty to unset it
 */
async function updateProperty(newValue: string) {
	try {
		await savePrimaryAccountProperty(birthdate.name, newValue ? new Date(newValue).toISOString() : '')
		savedValue = newValue
	} catch (error) {
		value.value = savedValue
		handleError(error, t('settings', 'Unable to update date of birth'))
	}
}
</script>

<template>
	<section :class="$style.propertySection">
		<div :class="$style.property">
			<NcDateTimePickerNative
				:id="`account-property-${birthdate.name}`"
				ref="picker"
				:class="$style.field"
				type="date"
				:label="readable"
				:min="minDate"
				:max="maxDate"
				:modelValue="timezoneAdjustedValue"
				@update:modelValue="onInput" />
			<div :class="$style.scope">
				<VisibilityScopeControl
					v-model:scope="scope"
					:readable="readable"
					:name="birthdate.name" />
			</div>
		</div>
	</section>
</template>

<style module>
.propertySection {
	padding: 6px 0;
}

.property {
	position: relative;
}

.field {
	width: 100%;
}

.scope {
	position: absolute;
	inset-block-end: 0; /* due to NcDateTimePickerNative's label handling, we need to use inset-block-end instead of block start */
	inset-inline-start: calc(100% + 8px);
	display: flex;
	align-items: center;
	justify-content: center;
	width: var(--default-clickable-area);
	height: var(--default-clickable-area);
}
</style>
