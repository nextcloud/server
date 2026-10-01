<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div :class="$style.timeslot">
		<input
			v-model="newValue.startTime"
			:aria-label="t('workflowengine', 'Start time')"
			:class="[$style.time, $style.startTime]"
			placeholder="e.g. 08:00"
			type="text"
			@input="update">
		<input
			v-model="newValue.endTime"
			:aria-label="t('workflowengine', 'End time')"
			:class="$style.time"
			placeholder="e.g. 18:00"
			type="text"
			@input="update">
		<p v-if="!valid" :class="$style.invalidHint">
			{{ t('workflowengine', 'Please enter a valid time span') }}
		</p>
		<NcSelect
			v-show="valid"
			v-model="newValue.timezone"
			:aria-label-combobox="t('workflowengine', 'Timezone')"
			:class="$style.timezone"
			:clearable="false"
			:options="timezones"
			@update:modelValue="update" />
	</div>
</template>

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { onBeforeMount, ref } from 'vue'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { useCheckValue } from '../../composables/useCheckValue.ts'
import { currentTimezone, isKnownTimezone, listTimezones } from '../../helpers/timezones.ts'

interface TimeSpan {
	startTime: string | null
	endTime: string | null
	timezone: string
}

const props = withDefaults(defineProps<{ modelValue?: string }>(), { modelValue: '[]' })

const emit = defineEmits<{
	'update:modelValue': [value: string]
	valid: []
	invalid: []
}>()

const TIME_PATTERN = /^(0[0-9]|1[0-9]|2[0-3]|[0-9]):[0-5][0-9]$/

const timezones = listTimezones()
const valid = ref(false)

/**
 * Split the stored `["HH:MM Zone","HH:MM Zone"]` pair into its parts.
 *
 * @param modelValue - The stored value
 */
function parseTimeSpan(modelValue: string): TimeSpan {
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
	return { startTime: null, endTime: null, timezone: currentTimezone() }
}

const { newValue, emitValue } = useCheckValue<TimeSpan>(
	() => props.modelValue,
	(value) => emit('update:modelValue', value),
	{
		parse: parseTimeSpan,
		format: ({ startTime, endTime, timezone }) => `["${startTime} ${timezone}","${endTime} ${timezone}"]`,
	},
)

/**
 * Both ends have to be a time of day and the zone has to be one the browser knows.
 */
function validate(): boolean {
	const { startTime, endTime, timezone } = newValue.value
	valid.value = Boolean(startTime) && TIME_PATTERN.test(startTime!)
		&& Boolean(endTime) && TIME_PATTERN.test(endTime!)
		&& isKnownTimezone(timezone)
	if (valid.value) {
		emit('valid')
	} else {
		emit('invalid')
	}
	return valid.value
}

function update(): void {
	if (newValue.value.timezone === null) {
		newValue.value.timezone = currentTimezone()
	}
	if (validate()) {
		emitValue()
	}
}

onBeforeMount(validate)
</script>

<style module lang="scss">
.timeslot {
	display: flex;
	flex-grow: 1;
	flex-wrap: wrap;
	max-width: 180px;
}

.timezone {
	width: 100%;
	margin-bottom: 5px;
}

.time {
	width: 50%;
	margin: 0;
	margin-bottom: 5px;
	min-height: 48px;
}

.startTime {
	margin-inline-end: 5px;
	width: calc(50% - 5px);
}

.invalidHint {
	color: var(--color-text-maxcontrast);
}
</style>
