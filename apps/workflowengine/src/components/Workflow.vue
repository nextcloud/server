<template>
	<div id="workflowengine">
		<div class="section">
			<h2>{{ t('workflowengine', 'Workflows') }}</h2>

			<transition-group name="slide" tag="div" class="actions">
				<Operation v-for="operation in getMainOperations"
					:key="operation.id"
					:operation="operation"
					@click.native="createNewRule(operation)" />
			</transition-group>

			<div v-if="hasMoreOperations" class="actions__more">
				<button class="icon"
					:class="showMoreOperations ? 'icon-triangle-n' : 'icon-triangle-s'"
					@click="showMoreOperations=!showMoreOperations">
					{{ showMoreOperations ? t('workflowengine', 'Show less') : t('workflowengine', 'Show more') }}
				</button>
			</div>
		</div>

		<transition-group v-if="rules.length > 0" name="slide">
			<Rule v-for="rule in rules" :key="rule.id" :rule="rule" />
		</transition-group>
	</div>
</template>

<script>
import Rule from './Rule'
import Operation from './Operation'
import { mapGetters, mapState } from 'vuex'

const ACTION_LIMIT = 3

export default {
	name: 'Workflow',
	components: {
		Operation,
		Rule
	},
	data() {
		return {
			showMoreOperations: false
		}
	},
	computed: {
		...mapGetters({
			rules: 'getRules'
		}),
		...mapState({
			operations: 'operations'
		}),
		hasMoreOperations() {
			return Object.keys(this.operations).length > ACTION_LIMIT
		},
		getMainOperations() {
			if (this.showMoreOperations) {
				return Object.values(this.operations)
			}
			return Object.values(this.operations).slice(0, ACTION_LIMIT)
		}
	},
	mounted() {
		this.$store.dispatch('fetchRules')
	},
	methods: {
		createNewRule(operation) {
			this.$store.dispatch('createNewRule', operation)
		}
	}
}
</script>

<style scoped lang="scss">
	#workflowengine {
		border-bottom: 1px solid var(--color-border);
	}
	.section {
		max-width: 100vw;
	}
	.actions {
		display: flex;
		flex-wrap: wrap;
		max-width: 900px;
		.actions__item {
			max-width: 280px;
			flex-basis: 250px;
		}
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
		padding-top: 0;
		padding-bottom: 0;
	}
</style>
