<template>
	<div>
		<div class="time-zone">
			<label :for="`vs${timeZonePickerId}__combobox`" class="time-zone__heading">
				{{ $t('dav', 'Time zone:') }}
			</label>
			<span class="time-zone-text">
				<NcTimezonePicker v-model="timezone" :uid="timeZonePickerId" />
			</span>
		</div>

		<CalendarAvailability :slots.sync="slots"
			:loading="loading"
			:l10n-to="$t('dav', 'to')"
			:l10n-delete-slot="$t('dav', 'Delete slot')"
			:l10n-empty-day="$t('dav', 'No working hours set')"
			:l10n-add-slot="$t('dav', 'Add slot')"
			:l10n-monday="$t('dav', 'Monday')"
			:l10n-tuesday="$t('dav', 'Tuesday')"
			:l10n-wednesday="$t('dav', 'Wednesday')"
			:l10n-thursday="$t('dav', 'Thursday')"
			:l10n-friday="$t('dav', 'Friday')"
			:l10n-saturday="$t('dav', 'Saturday')"
			:l10n-sunday="$t('dav', 'Sunday')"
			:l10n-start-picker-label="(dayName) => $t('dav', 'Pick a start time for {dayName}', { dayName })"
			:l10n-end-picker-label="(dayName) => $t('dav', 'Pick a end time for {dayName}', { dayName })" />

		<NcCheckboxRadioSwitch :checked.sync="automated">
			{{ $t('dav', 'Automatically set user status to "Do not disturb" outside of availability to mute all notifications.') }}
		</NcCheckboxRadioSwitch>

		<NcButton :disabled="loading || saving"
			type="primary"
			@click="save">
			{{ $t('dav', 'Save') }}
		</NcButton>
	</div>
</template>

<script>
import { CalendarAvailability } from '@nextcloud/calendar-availability-vue'
import { loadState } from '@nextcloud/initial-state'
import {
	showError,
	showSuccess,
} from '@nextcloud/dialogs'
import {
	findScheduleInboxAvailability,
	getEmptySlots,
	saveScheduleInboxAvailability,
} from '../service/CalendarService.js'
import {
	enableUserStatusAutomation,
	disableUserStatusAutomation,
} from '../service/PreferenceService.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcTimezonePicker from '@nextcloud/vue/dist/Components/NcTimezonePicker.js'

export default {
	name: 'AvailabilityForm',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		CalendarAvailability,
		NcTimezonePicker,
	},
	data() {
		// Try to determine the current timezone, and fall back to UTC otherwise
		const defaultTimezoneId = (new Intl.DateTimeFormat())?.resolvedOptions()?.timeZone ?? 'UTC'

		return {
			loading: true,
			saving: false,
			timezone: defaultTimezoneId,
			slots: getEmptySlots(),
			automated: loadState('dav', 'user_status_automation') === 'yes',
		}
	},
	computed: {
		timeZonePickerId() {
			return `tz-${(Math.random() + 1).toString(36).substring(7)}`
		},
	},
	async mounted() {
		try {
			const slotData = await findScheduleInboxAvailability()
			if (!slotData) {
				console.info('no availability is set')
				this.slots = getEmptySlots()
			} else {
				const { slots, timezoneId } = slotData
				this.slots = slots
				if (timezoneId) {
					this.timezone = timezoneId
				}
				console.info('availability loaded', this.slots, this.timezoneId)
			}
		} catch (e) {
			console.error('could not load existing availability', e)

			showError(t('dav', 'Failed to load availability'))
		} finally {
			this.loading = false
		}
	},
	methods: {
		async save() {
			try {
				this.saving = true

				await saveScheduleInboxAvailability(this.slots, this.timezone)
				if (this.automated) {
					await enableUserStatusAutomation()
				} else {
					await disableUserStatusAutomation()
				}

				showSuccess(t('dav', 'Saved availability'))
			} catch (e) {
				console.error('could not save availability', e)

				showError(t('dav', 'Failed to save availability'))
			} finally {
				this.saving = false
			}
		},
	},
}
</script>

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
:deep(.multiselect) {
	border: 1px solid var(--color-border-dark);
	width: 120px;
}
.time-zone {
	padding: 32px 12px 12px 0;
    display: flex;
    flex-wrap: wrap;

	&__heading {
		margin-right: calc(var(--default-grid-baseline) * 2);
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
	padding-right: 12px;
}

.empty-content {
	color: var(--color-text-lighter);
	margin-top: 4px;
	align-self: center;
}
</style>
