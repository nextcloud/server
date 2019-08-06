<template>
	<div class="section rule">
		<!-- TODO: icon-confirm -->
		<div class="trigger icon-confirm">
			<p>
				<span>{{ t('workflowengine', 'When') }}</span>
				<Event :rule="rule" @update="updateRule"></Event>
			</p>
			<p v-for="check in rule.checks">
				<span>{{ t('workflowengine', 'and') }}</span>
				<Check :check="check" @update="updateRule" @remove="removeCheck(check)"></Check>
			</p>
			<p>
				<span> </span>
				<input v-if="lastCheckComplete" type="button" class="check--add" @click="rule.checks.push({class: null, operator: null, value: null})" value="Add a new filter"/>
			</p>
		</div>
		<div class="action">
			<div class="buttons">
				<button class="status-button icon" :class="ruleStatus.class" v-tooltip="ruleStatus.tooltip" @click="saveRule">{{ ruleStatus.title }}</button>
				<Actions>
					<ActionButton v-if="rule.id === -1" icon="icon-close" @click="$emit('cancel')">Cancel</ActionButton>
					<ActionButton v-else icon="icon-delete" @click="deleteRule">Delete</ActionButton>
				</Actions>
			</div>
			<Operation :icon="operation.icon" :title="operation.title" :description="operation.description">
				<component v-if="operation.options" :is="operation.options" v-model="operation.operation" @input="updateOperation" />
			</Operation>
		</div>
	</div>
</template>

<script>
	import { Actions, ActionButton, Tooltip } from 'nextcloud-vue'
	import Event from './Event'
	import Check from './Check'
	import Operation from './Operation'
	import { operationService } from '../services/Operation'
	import axios from 'nextcloud-axios'
	import confirmPassword from 'nextcloud-password-confirmation'

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
				required: true,
			}
		},
		data () {
			return {
				editing: false,
				operationService,
				checks: [],
				error: null,
				dirty: this.rule.id === -1,
				checking: false
			}
		},
		computed: {
			operation() {
				return this.operationService.get(this.rule.class)
			},
			ruleStatus() {
				if (this.error) {
					return { title: 'Invalid', class: 'icon-close-white invalid', tooltip: { placement: 'bottom', show: true, content: escapeHTML(this.error.data) } }
				}
				if (!this.dirty || this.checking) {
					return { title: 'Active', class: 'icon icon-checkmark' }
				}
				return { title: 'Save', class: 'icon-confirm-white primary' }


			},
			lastCheckComplete() {
				const lastCheck = this.rule.checks[this.rule.checks.length-1]
				return typeof lastCheck === 'undefined' || lastCheck.class !== null
			}
		},
		methods: {
			updateOperation(operation) {
				this.$set(this.rule, 'operation', operation)
			},
			async updateRule() {
				this.checking = true
				if (!this.dirty) {
					this.dirty = true
				}
				try {
					let result = await axios.post(OC.generateUrl(`/apps/workflowengine/operations/test`), this.rule)
					this.error = null
					this.checking = false
				} catch (e) {
					console.error('Failed to update operation')
					this.error = e.response
					this.checking = false
				}
			},
			async saveRule() {
				try {
					await confirmPassword()
					let result
					if (this.rule.id === -1) {
						result = await axios.post(OC.generateUrl(`/apps/workflowengine/operations`), this.rule)
						this.rule.id = result.id
					} else {
						result = await axios.put(OC.generateUrl(`/apps/workflowengine/operations/${this.rule.id}`), this.rule)
					}
					this.$emit('update', result.data)
					this.dirty = false
					this.error = null
				} catch (e) {
					console.error('Failed to update operation')
					this.error = e.response
				}
			},
			async deleteRule() {
				try {
					await confirmPassword()
					await axios.delete(OC.generateUrl(`/apps/workflowengine/operations/${this.rule.id}`))
					this.$emit('delete')
				} catch (e) {
					console.error('Failed to delete operation')
					this.error = e.response
				}
			},
			removeCheck(check) {
				const index = this.rule.checks.findIndex(item => item === check)
				if (index !== -1) {
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
	}

	.rule {
		display: flex;
		.trigger, .action {
			flex-grow: 1;
			min-height: 100px;
			width: 50%;
		}
		.action {
			position: relative;
			.buttons {
				position: absolute;
				right: 0;
			}
		}
		.icon-confirm {
			background-position: right center;
		}
	}
	.trigger p, .action p {
		display: flex;
		align-items: center;
		margin-bottom: 5px;

		& > span {
			min-width: 50px;
			text-align: right;
			color: var(--color-text-light);
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
		width: 160px;
		border-radius: var(--border-radius);
		font-weight: normal;
		text-align: left;
		font-size: 1em;
	}
</style>