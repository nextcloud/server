<template>
	<div class="section rule" :style="{ borderLeftColor: operation.color }">
		<!-- TODO: icon-confirm -->
		<div class="trigger icon-confirm">
			<p>
				<span>{{ t('workflowengine', 'When') }}</span>
				<Event :rule="rule" @update="updateRule" />
			</p>
			<p v-for="check in rule.checks">
				<span>{{ t('workflowengine', 'and') }}</span>
				<Check :check="check" @update="updateRule" @remove="removeCheck(check)" />
			</p>
			<p>
				<span />
				<di<input v-if="lastCheckComplete" type="button" class="check--add"
					value="Add a new filter" @click="rule.checks.push({class: null, operator: null, value: null})">
			</p>
		</div>
		<div class="action">
			<div class="buttons">
				<Actions>
					<ActionButton v-if="rule.id < -1" icon="icon-close" @click="cancelRule">
						Cancel rule creation
					</ActionButton>
					<ActionButton v-else icon="icon-close" @click="deleteRule">
						Remove rule
					</ActionButton>
				</Actions>
				<button v-tooltip="ruleStatus.tooltip" class="status-button icon" :class="ruleStatus.class"
					@click="saveRule">
					{{ ruleStatus.title }}
				</button>
			</div>
			<Operation :operation="operation" :colored="false">
				<component :is="operation.options" v-if="operation.options" v-model="rule.operation"
					@input="updateOperation" />
			</Operation>
		</div>
	</div>
</template>

<script>
import { Actions, ActionButton, Tooltip } from 'nextcloud-vue'
import Event from './Event'
import Check from './Check'
import Operation from './Operation'

export default {
	name: 'Rule',
	components: {
		Operation, Check, Event, Actions, ActionButton
	},
	directives: {
		Tooltip
	},
	props: {
		rule: {
			type: Object,
			required: true
		}
	},
	data() {
		return {
			editing: false,
			checks: [],
			error: null,
			dirty: this.rule.id < 0,
			checking: false
		}
	},
	computed: {
		operation() {
			return this.$store.getters.getOperationForRule(this.rule)
		},
		ruleStatus() {
			if (this.error) {
				return {
					title: t('workflowengine', 'The configuration is invalid'),
					class: 'icon-close-white invalid',
					tooltip: { placement: 'bottom', show: true, content: this.error }
				}
			}
			if (!this.dirty || this.checking) {
				return { title: 'Active', class: 'icon icon-checkmark' }
			}
			return { title: 'Save', class: 'icon-confirm-white primary' }

		},
		lastCheckComplete() {
			const lastCheck = this.rule.checks[this.rule.checks.length - 1]
			return typeof lastCheck === 'undefined' || lastCheck.class !== null
		}
	},
	methods: {
		async updateOperation(operation) {
			this.$set(this.rule, 'operation', operation)
			await this.updateRule()
		},
		async updateRule() {
			this.checking = true
			if (!this.dirty) {
				this.dirty = true
			}
			try {
				// TODO: add new verify endpoint
				// let result = await axios.post(OC.generateUrl(`/apps/workflowengine/operations/test`), this.rule)
				this.error = null
				this.checking = false
				this.$store.dispatch('updateRule', this.rule)
			} catch (e) {
				console.error('Failed to update operation', e)
				this.error = e.response.ocs.meta.message
				this.checking = false
			}
		},
		async saveRule() {
			try {
				await this.$store.dispatch('pushUpdateRule', this.rule)
				this.dirty = false
				this.error = null
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
			this.$store.dispatch('removeRule', this.rule)
		},
		removeCheck(check) {
			const index = this.rule.checks.findIndex(item => item === check)
			if (index < 0) {
				this.rule.checks.splice(index, 1)
			}
		}
	}
}
</script>

<style scoped lang="scss">
	button.icon {
		padding-left: 32px;
		background-position: 10px center;
	}

	.status-button {
		transition: 0.5s ease all;
	}
	.status-button.primary {
		padding-left: 32px;
		background-position: 10px center;
	}
	.status-button:not(.primary) {
		background-color: var(--color-main-background);
	}
	.status-button.invalid {
		background-color: var(--color-warning);
		color: #fff;
		border: none;
	}

	.rule {
		display: flex;
		flex-wrap: wrap;
		border-left: 5px solid #fff;

		.trigger, .action {
			flex-grow: 1;
			min-height: 100px;
			max-width: 900px;
		}
		.action {
			max-width: 400px;
			position: relative;
			.buttons {
				position: absolute;
				right: 0;
				display: flex;
				z-index: 1;
			}
		}
		.icon-confirm {
			background-position: right center;
			padding-right: 20px;
			margin-right: 20px;
		}
	}
	.trigger p, .action p {
		display: flex;
		align-items: center;
		margin-bottom: 5px;

		& > span {
			min-width: 50px;
			text-align: right;
			color: var(--color-text-maxcontrast);
			padding-right: 5px;
		}
		.multiselect {
			flex-grow: 1;
			max-width: 300px;
		}
	}

	.check--add {
		background-position: 7px center;
		background-color: transparent;
		padding-left: 6px;
		margin: 0;
		width: 200px;
		border-radius: var(--border-radius);
		font-weight: normal;
		text-align: left;
		font-size: 1em;
	}
</style>
