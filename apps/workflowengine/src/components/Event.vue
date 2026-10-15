<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div :class="$style.event">
		<div v-if="operation.isComplex && operation.fixedEntity !== ''">
			<img :class="$style.optionIcon" :src="entity?.icon" alt="">
			<span :class="[$style.optionTitle, $style.singleTitle]">{{ operation.triggerHint }}</span>
		</div>
		<NcSelect
			v-else
			:aria-label-combobox="t('workflowengine', 'Trigger')"
			:class="$style.trigger"
			:disabled="allEvents.length <= 1"
			:modelValue="currentEvent"
			:multiple="true"
			:options="allEvents"
			:placeholder="placeholderString"
			label="displayName"
			@update:modelValue="updateEvent">
			<template #option="option">
				<img :class="$style.optionIcon" :src="option.entity.icon" alt="">
				<span :class="$style.optionTitle">{{ option.displayName }}</span>
			</template>
			<template #selected-option="option">
				<img :class="$style.optionIcon" :src="option.entity.icon" alt="">
				<span :class="$style.optionTitle">{{ option.displayName }}</span>
			</template>
		</NcSelect>
	</div>
</template>

<script setup lang="ts">
/* eslint vue/multi-word-component-names: "warn" */

import type { FlatEntityEvent, Rule } from '../types.ts'

import { showWarning } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import { useWorkflowStore } from '../store.ts'

const props = defineProps<{ rule: Rule }>()

const emit = defineEmits<{ update: [rule: Rule] }>()

const store = useWorkflowStore()

const operation = computed(() => store.operationForRule(props.rule)!)
const entity = computed(() => store.entityForOperation(operation.value))
const allEvents = computed(() => store.events)

const currentEvent = computed(() => allEvents.value.filter((event) => event.entity.id === props.rule.entity && props.rule.events.includes(event.eventName)))

// TRANSLATORS: Users should select a trigger for a workflow action
const placeholderString = t('workflowengine', 'Select a trigger')

/**
 * Apply the picked triggers. Picking events of another entity switches the rule
 * over to that entity, because a rule only ever watches one.
 *
 * @param events - The triggers that are now picked
 */
function updateEvent(events: FlatEntityEvent[]): void {
	if (events.length === 0) {
		// TRANSLATORS: Users must select an event as of "happening" or "incident" which triggers an action
		showWarning(t('workflowengine', 'At least one event must be selected'))
		return
	}

	const existingEntity = props.rule.entity
	const newEntities = [...new Set(events.map((event) => event.entity.id))]
	const newEntity = newEntities.length > 1
		? newEntities.filter((entityId) => entityId !== existingEntity)[0]
		: newEntities[0]

	store.setRuleTrigger(
		props.rule,
		newEntity,
		events.filter((event) => event.entity.id === newEntity).map((event) => event.eventName),
	)
	emit('update', props.rule)
}
</script>

<style module lang="scss">
.event {
	margin-bottom: 5px;

	img {
		vertical-align: text-top;
	}
}

.trigger {
	max-width: 550px;
}

.optionTitle {
	margin-inline-start: 5px;
	color: var(--color-main-text);
}

.singleTitle {
	padding-top: 2px;
	display: inline-block;
}

.optionIcon {
	width: 16px;
	height: 16px;
	filter: var(--background-invert-if-dark);
}
</style>
