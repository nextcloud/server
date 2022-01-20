<template>
	<div class="section">
		<h2>{{ $t('dav', 'Availability') }}</h2>
		<p>
			{{ $t('dav', 'If you configure your working hours, other users will see when you are out of office when they book a meeting.') }}
		</p>
		<div class="time-zone">
			<strong>
				{{ $t('calendar', 'Time zone:') }}
			</strong>
			<span class="time-zone-text">
				<TimezonePicker v-model="timezone" />
			</span>
		</div>
		<div class="grid-table">
			<template v-for="day in daysOfTheWeek">
				<div :key="`day-label-${day.id}`" class="label-weekday">
					{{ day.displayName }}
				</div>
				<div :key="`day-slots-${day.id}`" class="availability-slots">
					<div class="availability-slot-group">
						<template v-for="(slot, idx) in day.slots">
							<div :key="`slot-${day.id}-${idx}`" class="availability-slot">
								<DatetimePicker v-model="slot.start"
									type="time"
									class="start-date"
									format="H:mm" />
								<span class="to-text">
									{{ $t('dav', 'to') }}
								</span>
								<DatetimePicker v-model="slot.end"
									type="time"
									class="end-date"
									format="H:mm" />
								<button :key="`slot-${day.id}-${idx}-btn`"
									class="icon-delete delete-slot button"
									:title="$t('dav', 'Delete slot')"
									@click="deleteSlot(day, idx)" />
							</div>
						</template>
					</div>
					<span v-if="day.slots.length === 0"
						class="empty-content">
						{{ $t('dav', 'No working hours set') }}
					</span>
				</div>
				<button :key="`add-slot-${day.id}`"
					:disabled="loading"
					class="icon-add add-another button"
					:title="$t('dav', 'Add slot')"
					@click="addSlot(day)" />
			</template>
		</div>
		<button :disabled="loading || saving"
			class="button primary"
			@click="save">
			{{ $t('dav', 'Save') }}
		</button>
	</div>
</template>

<script>
import DatetimePicker from '@nextcloud/vue/dist/Components/DatetimePicker'
import {
	findScheduleInboxAvailability,
	getEmptySlots,
	saveScheduleInboxAvailability,
} from '../service/CalendarService'
import { getFirstDay } from '@nextcloud/l10n'
import jstz from 'jstimezonedetect'
import TimezonePicker from '@nextcloud/vue/dist/Components/TimezonePicker'
export default {
	name: 'Availability',
	components: {
		DatetimePicker,
		TimezonePicker,
	},
	data() {
		// Try to determine the current timezone, and fall back to UTC otherwise
		const defaultTimezone = jstz.determine()
		const defaultTimezoneId = defaultTimezone ? defaultTimezone.name() : 'UTC'

		const moToSa = [
			{
				id: 'MO',
				displayName: this.$t('dav', 'Monday'),
				slots: [],
			},
			{
				id: 'TU',
				displayName: this.$t('dav', 'Tuesday'),
				slots: [],
			},
			{
				id: 'WE',
				displayName: this.$t('dav', 'Wednesday'),
				slots: [],
			},
			{
				id: 'TH',
				displayName: this.$t('dav', 'Thursday'),
				slots: [],
			},
			{
				id: 'FR',
				displayName: this.$t('dav', 'Friday'),
				slots: [],
			},
			{
				id: 'SA',
				displayName: this.$t('dav', 'Saturday'),
				slots: [],
			},
		]
		const sunday = {
			id: 'SU',
			displayName: this.$t('dav', 'Sunday'),
			slots: [],
		}
		const daysOfTheWeek = getFirstDay() === 1 ? [...moToSa, sunday] : [sunday, ...moToSa]
		return {
			loading: true,
			saving: false,
			timezone: defaultTimezoneId,
			daysOfTheWeek,
		}
	},
	async mounted() {
		try {
			const { slots, timezoneId } = await findScheduleInboxAvailability()
			if (slots) {
				this.daysOfTheWeek.forEach(day => {
					day.slots.push(...slots[day.id])
				})
			}
			if (timezoneId) {
				this.timezone = timezoneId
			}
			console.info('availability loaded', this.daysOfTheWeek)
		} catch (e) {
			console.error('could not load existing availability', e)

			// TODO: show a nice toast
		} finally {
			this.loading = false
		}
	},
	methods: {
		addSlot(day) {
			const start = new Date()
			start.setHours(9)
			start.setMinutes(0)
			start.setSeconds(0)
			const end = new Date()
			end.setHours(17)
			end.setMinutes(0)
			end.setSeconds(0)
			day.slots.push({
				start,
				end,
			})
		},
		deleteSlot(day, idx) {
			day.slots.splice(idx, 1)
		},
		async save() {
			try {
				this.saving = true

				const slots = getEmptySlots()
				this.daysOfTheWeek.forEach(day => {
					day.slots.forEach(slot => slots[day.id].push(slot))
				})
				await saveScheduleInboxAvailability(slots, this.timezone)

				// TODO: show a nice toast
			} catch (e) {
				console.error('could not save availability', e)

				// TODO: show a nice toast
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
