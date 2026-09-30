<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="timeslot">
		<input
			v-model="newValue.startTime"
			:aria-label="t('workflowengine', 'Start time')"
			type="text"
			class="timeslot--start"
			placeholder="e.g. 08:00"
			@input="update">
		<input
			v-model="newValue.endTime"
			:aria-label="t('workflowengine', 'End time')"
			type="text"
			placeholder="e.g. 18:00"
			@input="update">
		<p v-if="!valid" class="invalid-hint">
			{{ t('workflowengine', 'Please enter a valid time span') }}
		</p>
		<NcSelect
			v-show="valid"
			v-model="newValue.timezone"
			:aria-label-combobox="t('workflowengine', 'Timezone')"
			:clearable="false"
			:options="timezones"
			@input="update" />
	</div>
</template>

<script>
import moment from 'moment-timezone'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { useCheckValue } from '../../composables/useCheckValue.ts'

const zones = moment.tz.names()
const TIME_PATTERN = /^(0[0-9]|1[0-9]|2[0-3]|[0-9]):[0-5][0-9]$/i

/**
 * Split the stored `["HH:MM Zone","HH:MM Zone"]` pair into its parts.
 *
 * @param {string} modelValue - The stored value
 */
function parseTimeSpan(modelValue) {
	try {
		const data = JSON.parse(modelValue)
		if (data.length === 2) {
			return {
				startTime: data[0].split(' ', 2)[0],
				endTime: data[1].split(' ', 2)[0],
				timezone: data[0].split(' ', 2)[1],
			}
		}
	} catch {
		// ignore invalid values
	}
	return { startTime: null, endTime: null, timezone: moment.tz.guess() }
}

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

	emits: ['update:model-value', 'valid', 'invalid'],

	setup(props, { emit }) {
		return useCheckValue(
			() => props.modelValue,
			(value) => emit('update:model-value', value),
			{
				parse: parseTimeSpan,
				format: ({ startTime, endTime, timezone }) => `["${startTime} ${timezone}","${endTime} ${timezone}"]`,
			},
		)
	},

	data() {
		return {
			timezones: zones,
			valid: false,
		}
	},

	beforeMount() {
		this.validate()
	},

	methods: {
		validate() {
			this.valid = Boolean(this.newValue.startTime) && TIME_PATTERN.test(this.newValue.startTime)
				&& Boolean(this.newValue.endTime) && TIME_PATTERN.test(this.newValue.endTime)
				&& moment.tz.zone(this.newValue.timezone) !== null
			this.$emit(this.valid ? 'valid' : 'invalid')
			return this.valid
		},

		update() {
			if (this.newValue.timezone === null) {
				this.newValue.timezone = moment.tz.guess()
			}
			if (this.validate()) {
				this.emitValue()
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
