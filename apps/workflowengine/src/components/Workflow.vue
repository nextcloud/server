<template>
	<div>
		<div class="section">
			<h2>{{ t('workflowengine', 'Workflows') }}</h2>

			<div class="actions" v-if="!hasUnsavedRule">
				<Operation v-for="operation in getMainOperations" :key="operation.class"
						   :icon="operation.icon" :title="operation.title" :description="operation.description" :color="operation.color"
						   @click.native="createNewRule(operation)"></Operation>
			</div>
			<div class="actions__more" v-if="!hasUnsavedRule && hasMoreOperations">
				<button class="icon" :class="showMoreOperations ? 'icon-triangle-n' : 'icon-triangle-s'"
						@click="showMoreOperations=!showMoreOperations">
					{{ showMoreOperations ? t('workflowengine', 'Show less') : t('workflowengine', 'Show more') }}
				</button>
			</div>
			<transition name="slide">
				<div class="actions" v-if="!hasUnsavedRule && showMoreOperations && hasMoreOperations">
					<Operation v-for="operation in getMoreOperations" :key="operation.class" :icon="operation.icon" :title="operation.title" :description="operation.description"
							   @click.native="createNewRule(operation)"></Operation>
				</div>
			</transition>
		</div>

		<transition-group name="slide">
			<Rule v-for="rule in rules" :key="rule.id" :rule="rule" @delete="removeRule(rule)" @cancel="removeRule(rule)" @update="updateRule"></Rule>
		</transition-group>

	</div>
</template>

<script>
	import Rule from './Rule'
	import Operation from './Operation'
	import { operationService } from '../services/Operation'
	import axios from 'nextcloud-axios'

	const ACTION_LIMIT = 3

	export default {
		name: 'Workflow',
		components: {
			Operation,
			Rule
		},
		async mounted() {
			const { data } = await axios.get(OC.generateUrl('/apps/workflowengine/operations'))
			this.rules = data
		},
		data() {
			return {
				operations: operationService,
				showMoreOperations: false,
				rules: []
			}
		},
		computed: {
			hasMoreOperations() {
				return Object.keys(this.operations.getAll()).length > ACTION_LIMIT
			},
			getMainOperations() {
				return Object.values(this.operations.getAll()).slice(0, ACTION_LIMIT)
			},
			getMoreOperations() {
				return Object.values(this.operations.getAll()).slice(ACTION_LIMIT)

			},
			hasUnsavedRule() {
				return !!this.rules.find((rule) => rule.id === -1)
			}
		},
		methods: {
			updateRule(rule) {
				let index = this.rules.findIndex((item) => rule === item)
				this.$set(this.rules, index, rule)
			},
			removeRule(rule) {
				let index = this.rules.findIndex((item) => rule === item)
				this.rules.splice(index, 1)
			},
			showAllOperations() {

			},
			createNewRule(operation) {
				this.rules.unshift({
					id: -1,
					class: operation.class,
					name: '', // unused in the new ui, there for legacy reasons
					checks: [],
					operation: operation.operation || ''
				})
			}
		}
	}
</script>

<style scoped lang="scss">
	.actions {
		display: flex;
		.action__item {
			border: 1px solid var(--color-border);
		}
	}

	.actions__more {
		text-align: center;
	}

	button.icon {
		padding-left: 32px;
		background-position: 10px center;
	}


	.slide-enter-active {
		-moz-transition-duration: 0.3s;
		-webkit-transition-duration: 0.3s;
		-o-transition-duration: 0.3s;
		transition-duration: 0.3s;
		-moz-transition-timing-function: ease-in;
		-webkit-transition-timing-function: ease-in;
		-o-transition-timing-function: ease-in;
		transition-timing-function: ease-in;
	}

	.slide-leave-active {
		-moz-transition-duration: 0.3s;
		-webkit-transition-duration: 0.3s;
		-o-transition-duration: 0.3s;
		transition-duration: 0.3s;
		-moz-transition-timing-function: cubic-bezier(0, 1, 0.5, 1);
		-webkit-transition-timing-function: cubic-bezier(0, 1, 0.5, 1);
		-o-transition-timing-function: cubic-bezier(0, 1, 0.5, 1);
		transition-timing-function: cubic-bezier(0, 1, 0.5, 1);
	}

	.slide-enter-to, .slide-leave {
		max-height: 500px;
		overflow: hidden;
	}

	.slide-enter, .slide-leave-to {
		overflow: hidden;
		max-height: 0;
	}
</style>