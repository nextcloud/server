<template>
	<div>
	<Multiselect :value="currentEvent" :options="allEvents" label="name" track-by="id" :allow-empty="false" :disabled="allEvents.length <= 1" @input="updateEvent">
		<template slot="singleLabel" slot-scope="props">
			<span class="option__icon" :class="props.option.icon"></span>
			<span class="option__title option__title_single">{{ props.option.name }}</span>
		</template>
		<template slot="option" slot-scope="props">
			<span class="option__icon" :class="props.option.icon"></span>
			<span class="option__title">{{ props.option.name }}</span>
		</template>
	</Multiselect>
	</div>
</template>

<script>
	import { Multiselect } from 'nextcloud-vue'
	import { eventService, operationService } from '../services/Operation'

	export default {
		name: "Event",
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
			currentEvent() {
				if (typeof this.rule.event === 'undefined') {
					return this.allEvents.length > 0 ? this.allEvents[0] : null
				}
				return this.allEvents.find(event => event.id === this.rule.event)
			},
			allEvents() {
				return this.operation.events.map((eventName) => eventService.get(eventName))
			},
			operation() {
				return operationService.get(this.rule.class)
			}
		},
		methods: {
			updateEvent(event) {
				this.$set(this.rule, 'event', event.id)
				this.$emit('update', this.rule)
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