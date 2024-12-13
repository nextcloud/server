<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="event">
		<div v-if="operation.isComplex && operation.fixedEntity !== ''" class="isComplex">
			<img class="option__icon" :src="entity.icon" alt="">
			<span class="option__title option__title_single">{{ operation.triggerHint }}</span>
		</div>
		<NcSelect v-else
			:disabled="allEvents.length <= 1"
			:multiple="true"
			:options="allEvents"
			:value="currentEvent"
			:placeholder="placeholderString"
			class="event__trigger"
			label="displayName"
			@input="updateEvent">
			<template #option="option">
				<img class="option__icon" :src="option.entity.icon" alt="">
				<span class="option__title">{{ option.displayName }}</span>
			</template>
			<template #selected-option="option">
				<img class="option__icon" :src="option.entity.icon" alt="">
				<span class="option__title">{{ option.displayName }}</span>
			</template>
		</NcSelect>
	</div>
</template>

<script>
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import { showWarning } from '@nextcloud/dialogs'

export default {
	name: 'Event',
	components: {
		NcSelect,
	},
	props: {
		rule: {
			type: Object,
			required: true,
		},
	},
	computed: {
		entity() {
			return this.$store.getters.getEntityForOperation(this.operation)
		},
		operation() {
			return this.$store.getters.getOperationForRule(this.rule)
		},
		allEvents() {
			return this.$store.getters.getEventsForOperation(this.operation)
		},
		currentEvent() {
			return this.allEvents.filter(event => event.entity.id === this.rule.entity && this.rule.events.indexOf(event.eventName) !== -1)
		},
		placeholderString() {
			// TRANSLATORS: Users should select a trigger for a workflow action
			return t('workflowengine', 'Select a trigger')
		},
	},
	methods: {
		updateEvent(events) {
			if (events.length === 0) {
				// TRANSLATORS: Users must select an event as of "happening" or "incident" which triggers an action
				showWarning(t('workflowengine', 'At least one event must be selected'))
				return
			}
			const existingEntity = this.rule.entity
			const newEntities = events.map(event => event.entity.id).filter((value, index, self) => self.indexOf(value) === index)
			let newEntity = null
			if (newEntities.length > 1) {
				newEntity = newEntities.filter(entity => entity !== existingEntity)[0]
			} else {
				newEntity = newEntities[0]
			}

			this.$set(this.rule, 'entity', newEntity)
			this.$set(this.rule, 'events', events.filter(event => event.entity.id === newEntity).map(event => event.eventName))
			this.$emit('update', this.rule)
		},
	},
}
</script>

<style scoped lang="scss">
	.event {
		margin-bottom: 5px;

		&__trigger {
			max-width: 550px;
		}
	}

	.isComplex {
		img {
			vertical-align: text-top;
		}
		span {
			padding-top: 2px;
			display: inline-block;
		}
	}

	.option__title {
		margin-inline-start: 5px;
		color: var(--color-main-text);
	}

	.option__icon {
		width: 16px;
		height: 16px;
		filter: var(--background-invert-if-dark);
	}
</style>
