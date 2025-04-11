<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="timeslot">
		<input v-model="newValue.startTime"
			type="text"
			class="timeslot--start"
			placeholder="e.g. 08:00"
			@input="update">
		<input v-model="newValue.endTime"
			type="text"
			placeholder="e.g. 18:00"
			@input="update">
		<p v-if="!valid" class="invalid-hint">
			{{ t('workflowengine', 'Please enter a valid time span') }}
		</p>
		<NcSelect v-show="valid"
			v-model="newValue.timezone"
			:clearable="false"
			:options="timezones"
			@input="update" />
	</div>
</template>

<script>
import NcSelect from '@nextcloud/vue/components/NcSelect'
import moment from 'moment-timezone'

const zones = moment.tz.names()
export default {
	name: 'RequestTime',
	components: {
		NcSelect,
	},
	props: {
		modelValue: {
			type: String,
			default: '[]',
		},
	},
	emits: ['update:model-value'],
	data() {
		return {
			timezones: zones,
			valid: false,
			newValue: {
				startTime: null,
				endTime: null,
				timezone: moment.tz.guess(),
			},
			stringifiedValue: '[]',
		}
	},
	watch: {
		modelValue() {
			this.updateInternalValue()
		},
	},
	beforeMount() {
		// this is necessary to keep so the value is re-applied when a different
		// check is being removed.
		this.updateInternalValue()
	},
	methods: {
		updateInternalValue() {
			try {
				const data = JSON.parse(this.modelValue)
				if (data.length === 2) {
					this.newValue = {
						startTime: data[0].split(' ', 2)[0],
						endTime: data[1].split(' ', 2)[0],
						timezone: data[0].split(' ', 2)[1],
					}
					this.stringifiedValue = `["${this.newValue.startTime} ${this.newValue.timezone}","${this.newValue.endTime} ${this.newValue.timezone}"]`
					this.validate()
				}
			} catch (e) {
				// ignore invalid values
			}
		},
		validate() {
			this.valid = this.newValue.startTime && this.newValue.startTime.match(/^(0[0-9]|1[0-9]|2[0-3]|[0-9]):[0-5][0-9]$/i) !== null
				&& this.newValue.endTime && this.newValue.endTime.match(/^(0[0-9]|1[0-9]|2[0-3]|[0-9]):[0-5][0-9]$/i) !== null
				&& moment.tz.zone(this.newValue.timezone) !== null
			if (this.valid) {
				this.$emit('valid')
			} else {
				this.$emit('invalid')
			}
			return this.valid
		},
		update() {
			if (this.newValue.timezone === null) {
				this.newValue.timezone = moment.tz.guess()
			}
			if (this.validate()) {
				this.stringifiedValue = `["${this.newValue.startTime} ${this.newValue.timezone}","${this.newValue.endTime} ${this.newValue.timezone}"]`
				this.$emit('update:model-value', this.stringifiedValue)
			}
		},
	},
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

		.multiselect:deep(.multiselect__tags:not(:hover):not(:focus):not(:active)) {
			border: 1px solid transparent;
		}

		input[type=text] {
			width: 50%;
			margin: 0;
			margin-bottom: 5px;
			min-height: 48px;

			&.timeslot--start {
				margin-inline-end: 5px;
				width: calc(50% - 5px);
			}
		}

		.invalid-hint {
			color: var(--color-text-maxcontrast);
		}
	}
</style>
