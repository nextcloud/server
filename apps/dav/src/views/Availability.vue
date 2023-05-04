<template>
	<NcSettingsSection :title="$t('dav', 'Availability')"
		:description="$t('dav', 'If you configure your working hours, other users will see when you are out of office when they book a meeting.')">
		<div class="time-zone">
			<strong>
				{{ $t('dav', 'Time zone:') }}
			</strong>
			<span class="time-zone-text">
				<NcTimezonePicker v-model="timezone" />
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
			:l10n-sunday="$t('dav', 'Sunday')" />

		<NcCheckboxRadioSwitch :checked.sync="automated">
			{{ $t('dav', 'Automatically set user status to "Do not disturb" outside of availability to mute all notifications.') }}
		</NcCheckboxRadioSwitch>

		<NcButton :disabled="loading || saving"
			type="primary"
			@click="save">
			{{ $t('dav', 'Save') }}
		</NcButton>
	</NcSettingsSection>
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
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcTimezonePicker from '@nextcloud/vue/dist/Components/NcTimezonePicker.js'

export default {
	name: 'Availability',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		CalendarAvailability,
		NcSettingsSection,
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
.availability-day {
	padding: 0 10px 0 10px;
	position: absolute;
}
.availability-slots {
	display: flex;
	white-space: nowrap;
}
.availability-slot {
	display: flex;
	flex-direction: row;
	align-items: center;
}
.availability-slot-group {
	display: flex;
	flex-direction: column;
}
::v-deep .mx-input-wrapper {
	width: 85px;
}
::v-deep .mx-datepicker {
	width: 97px;
}
::v-deep .multiselect {
	border: 1px solid var(--color-border-dark);
	width: 120px;
}
.time-zone {
	padding: 32px 12px 12px 0;
}
.grid-table {
	display: grid;
	margin-bottom: 32px;
	grid-column-gap: 24px;
	grid-row-gap: 6px;
	grid-template-columns: min-content min-content min-content;
}
.button {
	align-self: flex-end;
}
.label-weekday {
	position: relative;
	display: inline-flex;
	padding-top: 4px;
}
.delete-slot {
	background-color: transparent;
	border: none;
	padding-bottom: 12px;
	opacity: .5;
	&:hover {
		opacity: 1;
	}
}

.add-another {
	background-color: transparent;
	border: none;
	opacity: .5;
	display: inline-flex;
	padding: 0;
	margin: 0;
	margin-bottom: 3px;

	&:hover {
		opacity: 1;
	}
}
.to-text {
	padding-right: 12px;
}
.time-zone-text{
	padding-left: 22px;
}
.empty-content {
	color: var(--color-text-lighter);
	margin-top: 4px;
}

</style>
