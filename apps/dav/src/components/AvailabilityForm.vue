<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { CalendarAvailability } from '@nextcloud/calendar-availability-vue'
import { getCapabilities } from '@nextcloud/capabilities'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { onMounted, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import {
	findScheduleInboxAvailability,
	getEmptySlots,
	saveScheduleInboxAvailability,
} from '../service/CalendarService.js'
import { logger } from '../service/logger.ts'
import {
	disableUserStatusAutomation,
	enableUserStatusAutomation,
} from '../service/PreferenceService.js'

// @ts-expect-error capabilities is missing the capability to type it...
const timezone = getCapabilities().core.user?.timezone ?? Intl.DateTimeFormat().resolvedOptions().timeZone

const loading = ref(true)
const saving = ref(false)
const slots = ref(getEmptySlots())
const automated = ref(loadState('dav', 'user_status_automation') === 'yes')

onMounted(async () => {
	try {
		const slotData = await findScheduleInboxAvailability()
		if (!slotData) {
			logger.debug('no availability is set')
		} else {
			slots.value = slotData.slots
			logger.debug('availability loaded', { slots: slots.value })
		}
	} catch (error) {
		logger.error('could not load existing availability', { error })
		showError(t('dav', 'Failed to load availability'))
	} finally {
		loading.value = false
	}
})

/**
 * Save current slots on the server
 */
async function save() {
	saving.value = true
	try {
		await saveScheduleInboxAvailability(slots.value, timezone)
		if (automated.value) {
			await enableUserStatusAutomation()
		} else {
			await disableUserStatusAutomation()
		}

		showSuccess(t('dav', 'Saved availability'))
	} catch (error) {
		logger.error('could not save availability', { error })
		showError(t('dav', 'Failed to save availability'))
	} finally {
		saving.value = false
	}
}
</script>

<template>
	<div>
		<CalendarAvailability
			v-model:slots="slots"
			:loading="loading"
			:l10n-to="t('dav', 'to')"
			:l10n-delete-slot="t('dav', 'Delete slot')"
			:l10n-empty-day="t('dav', 'No working hours set')"
			:l10n-add-slot="t('dav', 'Add slot')"
			:l10n-week-day-list-label="t('dav', 'Weekdays')"
			:l10n-monday="t('dav', 'Monday')"
			:l10n-tuesday="t('dav', 'Tuesday')"
			:l10n-wednesday="t('dav', 'Wednesday')"
			:l10n-thursday="t('dav', 'Thursday')"
			:l10n-friday="t('dav', 'Friday')"
			:l10n-saturday="t('dav', 'Saturday')"
			:l10n-sunday="t('dav', 'Sunday')"
			:l10n-start-picker-label="(dayName) => t('dav', 'Pick a start time for {dayName}', { dayName })"
			:l10n-end-picker-label="(dayName) => t('dav', 'Pick a end time for {dayName}', { dayName })" />

		<NcCheckboxRadioSwitch v-model="automated">
			{{ t('dav', 'Automatically set user status to "Do not disturb" outside of availability to mute all notifications.') }}
		</NcCheckboxRadioSwitch>

		<NcButton
			:disabled="loading || saving"
			variant="primary"
			@click="save">
			{{ t('dav', 'Save') }}
		</NcButton>
	</div>
</template>

<style lang="scss" scoped>
:deep(.availability-day) {
	padding: 0 10px 0 10px;
	position: absolute;
}

:deep(.availability-slots) {
	display: flex;
	white-space: normal;
}

:deep(.availability-slot) {
	display: flex;
	flex-direction: row;
	align-items: center;
	flex-wrap: wrap;
}

:deep(.availability-slot-group) {
	display: flex;
	flex-direction: column;
}

:deep(.mx-input-wrapper) {
	width: 85px;
}

:deep(.mx-datepicker) {
	width: 97px;
}

.time-zone {
	padding-block: 32px 12px;
	padding-inline: 0 12px;
	display: flex;
	flex-wrap: wrap;

	&__heading {
		margin-inline-end: calc(var(--default-grid-baseline) * 2);
		line-height: var(--default-clickable-area);
		font-weight: bold;
	}
}

.grid-table {
	display: grid;
	margin-bottom: 32px;
	grid-column-gap: 24px;
	grid-row-gap: 6px;
	grid-template-columns: min-content auto min-content;
	max-width: 500px;
}

.button {
	align-self: flex-end;
}

:deep(.label-weekday) {
	position: relative;
	display: inline-flex;
	padding-top: 4px;
	align-self: center;
}

:deep(.delete-slot) {
	padding-bottom: unset;
}

:deep(.add-another) {
	align-self: center;
}

.to-text {
	padding-inline-end: 12px;
}

.empty-content {
	align-self: center;
	color: var(--color-text-maxcontrast);
	margin-block-start: var(--default-grid-baseline);
}
</style>
