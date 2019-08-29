<template>
	<div>
		<div v-if="operation.isComplex && operation.fixedEntity !== ''">
			<img class="option__icon" :src="entity.icon">
			<span class="option__title option__title_single">{{ entity.name }}</span>
		</div>
		<Multiselect v-else :value="currentEvent" :options="allEvents"
			label="eventName" track-by="id" :allow-empty="false"
			:disabled="allEvents.length <= 1" @input="updateEvent">
			<template slot="singleLabel" slot-scope="props">
				<img class="option__icon" :src="props.option.entity.icon">
				<span class="option__title option__title_single">{{ props.option.displayName }}</span>
			</template>
			<template slot="option" slot-scope="props">
				<img class="option__icon" :src="props.option.entity.icon">
				<span class="option__title">{{ props.option.displayName }}</span>
			</template>
		</Multiselect>
	</div>
</template>

<script>
import { Multiselect } from 'nextcloud-vue'

export default {
	name: 'Event',
	components: {
		Multiselect
	},
	props: {
		rule: {
			type: Object,
			required: true
		}
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
			if (!this.rule.events) {
				return this.allEvents.length > 0 ? this.allEvents[0] : null
			}
			return this.allEvents.find(event => event.entity.id === this.rule.entity && this.rule.events.indexOf(event.eventName) !== -1)
		}
	},
	methods: {
		updateEvent(event) {
			this.$set(this.rule, 'entity', event.entity.id)
			this.$set(this.rule, 'events', [event.eventName])
			this.$store.dispatch('updateRule', this.rule)
		}
	}
}
</script>

<style scoped>
	.multiselect::v-deep .multiselect__single {
		display: flex;
	}
	.multiselect:not(.multiselect--active)::v-deep .multiselect__tags {
		background-color: var(--color-main-background) !important;
		border: 1px solid transparent;
	}

	.multiselect::v-deep .multiselect__tags .multiselect__single {
		background-color: var(--color-main-background) !important;
	}

	.multiselect:not(.multiselect--disabled)::v-deep .multiselect__tags .multiselect__single {
		background-image: var(--icon-triangle-s-000);
		background-repeat: no-repeat;
		background-position: right center;
	}

	input {
		border: 1px solid transparent;
	}

	.option__title {
		margin-left: 5px;
		color: var(--color-main-text);
	}
	.option__title_single {
		font-weight: 900;
	}
</style>
