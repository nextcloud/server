<template>
	<div class="timeslot">
		<Multiselect v-model="newValue.timezone" :options="timezones" @input="update" />
		<input v-model="newValue.startTime"
			type="text"
			class="timeslot--start"
			placeholder="08:00"
			@input="update">
		<input v-model="newValue.endTime"
			type="text"
			placeholder="18:00"
			@input="update">
	</div>
</template>

<script>
import { Multiselect } from 'nextcloud-vue/dist/Components/Multiselect'
import moment from 'moment-timezone'
import valueMixin from '../../mixins/valueMixin'

const zones = moment.tz.names()
export default {
	name: 'RequestTime',
	components: {
		Multiselect
	},
	mixins: [
		valueMixin
	],
	props: {
		value: {
			type: String,
			default: '1 MB'
		}
	},
	data() {
		return {
			timezones: zones,
			valid: false,
			newValue: {
				startTime: null,
				endTime: null,
				timezone: moment.tz.guess()
			}
		}
	},
	methods: {
		updateInternalValue(value) {
			var data = JSON.parse(value)
			var startTime = data[0].split(' ', 2)[0]
			var endTime = data[1].split(' ', 2)[0]
			var timezone = data[0].split(' ', 2)[1]
			this.newValue = {
				startTime: startTime,
				endTime: endTime,
				timezone: timezone
			}
		},
		validate() {
			return this.newValue.startTime && this.newValue.startTime.match(/^(0[0-9]|1[0-9]|2[0-3]|[0-9]):[0-5][0-9]$/i) !== null
				&& this.newValue.endTime && this.newValue.endTime.match(/^(0[0-9]|1[0-9]|2[0-3]|[0-9]):[0-5][0-9]$/i) !== null
				&& moment.tz.zone(this.newValue.timezone) !== null
		},
		update() {
			if (this.validate()) {
				const output = `["${this.newValue.startTime} ${this.newValue.timezone}","${this.newValue.endTime} ${this.newValue.timezone}"]`
				this.$emit('input', output)
				this.valid = true
			} else {
				this.valid = false
			}
		}
	}
}
</script>

<style scoped lang="scss">
	.timeslot {
		display: flex;
		flex-grow: 1;
		flex-wrap: wrap;
		max-width: 180px;

		.multiselect {
			width: 100%;
			margin-bottom: 5px;
		}

		input[type=text] {
			width: 50%;
			margin: 0;
			margin-bottom: 5px;
			&.timeslot--start {
				margin-right: 5px;
				width: calc(50% - 5px);
			}
		}
	}
</style>
