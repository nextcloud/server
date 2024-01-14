<template>
	<div v-if="operation" class="section rule" :style="{ borderLeftColor: operation.color || '' }">
		<div class="trigger">
			<p>
				<span>{{ t('workflowengine', 'When') }}</span>
				<Event :rule="rule" @update="updateRule" />
			</p>
			<p v-for="(check, index) in rule.checks" :key="index">
				<span>{{ t('workflowengine', 'and') }}</span>
				<Check :check="check"
					:rule="rule"
					@update="updateRule"
					@validate="validate"
					@remove="removeCheck(check)" />
			</p>
			<p>
				<span />
				<input v-if="lastCheckComplete"
					type="button"
					class="check--add"
					:value="t('workflowengine', 'Add a new filter')"
					@click="onAddFilter">
			</p>
		</div>
		<div class="flow-icon icon-confirm" />
		<div class="action">
			<Operation :operation="operation" :colored="false">
				<component :is="operation.options"
					v-if="operation.options"
					v-model="rule.operation"
					@input="updateOperation" />
			</Operation>
			<div class="buttons">
				<NcButton v-if="rule.id < -1 || dirty" @click="cancelRule">
					{{ t('workflowengine', 'Cancel') }}
				</NcButton>
				<NcButton v-else-if="!dirty" @click="deleteRule">
					{{ t('workflowengine', 'Delete') }}
				</NcButton>
				<NcButton :type="ruleStatus.type"
					@click="saveRule">
					<template #icon>
						<component :is="ruleStatus.icon" :size="20" />
					</template>
					{{ ruleStatus.title }}
				</NcButton>
			</div>
			<p v-if="error" class="error-message">
				{{ error }}
			</p>
		</div>
	</div>
</template>

<script>
import Tooltip from '@nextcloud/vue/dist/Directives/Tooltip.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import ArrowRight from 'vue-material-design-icons/ArrowRight.vue'
import CheckMark from 'vue-material-design-icons/Check.vue'
import Close from 'vue-material-design-icons/Close.vue'

import Event from './Event.vue'
import Check from './Check.vue'
import Operation from './Operation.vue'

export default {
	name: 'Rule',
	components: {
		ArrowRight,
		Check,
		CheckMark,
		Close,
		Event,
		NcActionButton,
		NcActions,
		NcButton,
		Operation,
	},
	directives: {
		Tooltip,
	},
	props: {
		rule: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			editing: false,
			checks: [],
			error: null,
			dirty: this.rule.id < 0,
			originalRule: null,
		}
	},
	computed: {
		operation() {
			return this.$store.getters.getOperationForRule(this.rule)
		},
		ruleStatus() {
			if (this.error || !this.rule.valid || this.rule.checks.length === 0 || this.rule.checks.some((check) => check.invalid === true)) {
				return {
					title: t('workflowengine', 'The configuration is invalid'),
					icon: 'Close',
					type: 'warning',
					tooltip: { placement: 'bottom', show: true, content: this.error },
				}
			}
			if (!this.dirty) {
				return { title: t('workflowengine', 'Active'), icon: 'CheckMark', type: 'success' }
			}
			return { title: t('workflowengine', 'Save'), icon: 'ArrowRight', type: 'primary' }

		},
		lastCheckComplete() {
			const lastCheck = this.rule.checks[this.rule.checks.length - 1]
			return typeof lastCheck === 'undefined' || lastCheck.class !== null
		},
	},
	mounted() {
		this.originalRule = JSON.parse(JSON.stringify(this.rule))
	},
	methods: {
		async updateOperation(operation) {
			this.$set(this.rule, 'operation', operation)
			await this.updateRule()
		},
		validate(state) {
			this.error = null
			this.$store.dispatch('updateRule', this.rule)
		},
		updateRule() {
			if (!this.dirty) {
				this.dirty = true
			}

			this.error = null
			this.$store.dispatch('updateRule', this.rule)
		},
		async saveRule() {
			try {
				await this.$store.dispatch('pushUpdateRule', this.rule)
				this.dirty = false
				this.error = null
				this.originalRule = JSON.parse(JSON.stringify(this.rule))
			} catch (e) {
				console.error('Failed to save operation')
				this.error = e.response.data.ocs.meta.message
			}
		},
		async deleteRule() {
			try {
				await this.$store.dispatch('deleteRule', this.rule)
			} catch (e) {
				console.error('Failed to delete operation')
				this.error = e.response.data.ocs.meta.message
			}
		},
		cancelRule() {
			if (this.rule.id < 0) {
				this.$store.dispatch('removeRule', this.rule)
			} else {
				this.$store.dispatch('updateRule', this.originalRule)
				this.originalRule = JSON.parse(JSON.stringify(this.rule))
				this.dirty = false
			}
		},

		async removeCheck(check) {
			const index = this.rule.checks.findIndex(item => item === check)
			if (index > -1) {
				this.$delete(this.rule.checks, index)
			}
			this.$store.dispatch('updateRule', this.rule)
		},

		onAddFilter() {
			// eslint-disable-next-line vue/no-mutating-props
			this.rule.checks.push({ class: null, operator: null, value: '' })
		},
	},
}
</script>

<style scoped lang="scss">

	.buttons {
		display: flex;
		justify-content: end;

		button {
			margin-left: 5px;
		}
		button:last-child{
			margin-right: 10px;
		}
	}

	.error-message {
		float: right;
		margin-right: 10px;
	}

	.flow-icon {
		width: 44px;
	}

	.rule {
		display: flex;
		flex-wrap: wrap;
		border-left: 5px solid var(--color-primary-element);

		.trigger,
		.action {
			flex-grow: 1;
			min-height: 100px;
			max-width: 920px;
		}
		.action {
			max-width: 400px;
			position: relative;
		}
		.icon-confirm {
			background-position: right 27px;
			padding-right: 20px;
			margin-right: 20px;
		}
	}
	.trigger p, .action p {
		min-height: 34px;
		display: flex;

		& > span {
			min-width: 50px;
			text-align: right;
			color: var(--color-text-maxcontrast);
			padding-right: 10px;
			padding-top: 6px;
		}
		.multiselect {
			flex-grow: 1;
			max-width: 300px;
		}
	}
	.trigger p:first-child span {
			padding-top: 3px;
	}
	.trigger p:last-child {
			padding-top: 8px;
	}

	.check--add {
		background-position: 7px center;
		background-color: transparent;
		padding-left: 6px;
		margin: 0;
		width: 180px;
		border-radius: var(--border-radius);
		color: var(--color-text-maxcontrast);
		font-weight: normal;
		text-align: left;
		font-size: 1em;
	}

	@media (max-width:1400px) {
		.rule {
			&, .trigger, .action {
				width: 100%;
				max-width: 100%;
			}
			.flow-icon {
				display: none;
			}
		}
	}

</style>
