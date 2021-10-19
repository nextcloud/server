<template>
	<div class="section">
		<h2>{{ $t('dav', 'Availability') }}</h2>
		<p>
			{{ $t('dav', 'If you configure your working hours, other users will see when you are out of office when they book a meeting.') }}
		</p>
		<div class="time-zone">
			<strong>
				{{ $t('calendar', 'Please select a time zone:') }}
			</strong>
			<TimezonePicker v-model="timezone" />
		</div>
		<div class="grid-table">
			<template v-for="day in daysOfTheWeek">
				<div :key="`day-label-${day.id}`" class="label-weekday">
					{{ day.displayName }}
				</div>
				<div :key="`day-slots-${day.id}`" class="availability-slots">
					<div class="availability-slot">
						<template v-for="(slot, idx) in day.slots">
							<div :key="`slot-${day.id}-${idx}`">
								<DatetimePicker
									v-model="slot.start"
									type="time"
									class="start-date"
									format="H:mm" />
								{{ $t('dav', 'to') }}
								<DatetimePicker
									v-model="slot.end"
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
					<button :disabled="loading"
						class="add-another button"
						@click="addSlot(day)">
						{{ $t('dav', 'Add slot') }}
					</button>
				</div>
			</template>
		</div>
		<button :disabled="loading || saving"
			class="button"
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
	padding: 0 10px 10px 10px;
	position: absolute;
}
.availability-slots {
	display: flex;
}
.availability-slot {
	display: flex;
	flex-direction: column;
}
::v-deep .mx-input-wrapper {
	width: 85px;
}
::v-deep .mx-datepicker {
	width: 110px;
}
::v-deep .multiselect {
	border: 1px solid var(--color-border-dark);
	width: 120px;
}
.time-zone {
	padding: 12px 12px 12px 0;
}
.grid-table {
	display: grid;
	grid-template-columns: min-content auto;
}
.button {
	align-self: flex-end;
}
.label-weekday {
	padding: 8px 23px 14px 0;
	position: relative;
	display: inline-flex;
}
.delete-slot {
	background-color: transparent;
	border: none;
	padding: 15px;
}

</style>
