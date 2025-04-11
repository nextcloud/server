<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
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
				<component :is="operation.element"
					v-if="operation.element"
					ref="operationComponent"
					:model-value="inputValue"
					@update:model-value="updateOperationByEvent" />
				<component :is="operation.options"
					v-else-if="operation.options"
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
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcButton from '@nextcloud/vue/components/NcButton'
import Tooltip from '@nextcloud/vue/directives/Tooltip'
import IconArrowRight from 'vue-material-design-icons/ArrowRight.vue'
import IconCheckMark from 'vue-material-design-icons/Check.vue'
import IconClose from 'vue-material-design-icons/Close.vue'

import Event from './Event.vue'
import Check from './Check.vue'
import Operation from './Operation.vue'

export default {
	name: 'Rule',
	components: {
		Check,
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
			element: null,
			inputValue: '',
		}
	},
	computed: {
		/**
		 * @return {OperatorPlugin}
		 */
		operation() {
			return this.$store.getters.getOperationForRule(this.rule)
		},
		ruleStatus() {
			if (this.error || !this.rule.valid || this.rule.checks.length === 0 || this.rule.checks.some((check) => check.invalid === true)) {
				return {
					title: t('workflowengine', 'The configuration is invalid'),
					icon: IconClose,
					type: 'warning',
					tooltip: { placement: 'bottom', show: true, content: this.error },
				}
			}
			if (!this.dirty) {
				return { title: t('workflowengine', 'Active'), icon: IconCheckMark, type: 'success' }
			}
			return { title: t('workflowengine', 'Save'), icon: IconArrowRight, type: 'primary' }

		},
		lastCheckComplete() {
			const lastCheck = this.rule.checks[this.rule.checks.length - 1]
			return typeof lastCheck === 'undefined' || lastCheck.class !== null
		},
	},
	mounted() {
		this.originalRule = JSON.parse(JSON.stringify(this.rule))

		if (this.operation?.element) {
			this.$refs.operationComponent.value = this.rule.operation
		} else if (this.operation?.options) {
			// keeping this in an else for apps that try to be backwards compatible and may ship both
			// to be removed in 03/2028
			console.warn('Developer warning: `OperatorPlugin.options` is deprecated. Use `OperatorPlugin.element` instead.')
		}
	},
	methods: {
		async updateOperation(operation) {
			this.$set(this.rule, 'operation', operation)
			this.updateRule()
		},
		async updateOperationByEvent(event) {
			this.inputValue = event.detail[0]
			this.$set(this.rule, 'operation', event.detail[0])
			this.updateRule()
		},
		validate(/* state */) {
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
				this.inputValue = this.originalRule.operation
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
			margin-inline-start: 5px;
		}
		button:last-child{
			margin-inline-end: 10px;
		}
	}

	.error-message {
		float: right;
		margin-inline-end: 10px;
	}

	.flow-icon {
		width: 44px;
	}

	.rule {
		display: flex;
		flex-wrap: wrap;
		border-inline-start: 5px solid var(--color-primary-element);

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
			padding-inline-end: 20px;
			margin-inline-end: 20px;
		}
	}

	.trigger p, .action p {
		min-height: 34px;
		display: flex;

		& > span {
			min-width: 50px;
			text-align: end;
			color: var(--color-text-maxcontrast);
			padding-inline-end: 10px;
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
		padding-inline-start: 6px;
		margin: 0;
		width: 180px;
		border-radius: var(--border-radius);
		color: var(--color-text-maxcontrast);
		font-weight: normal;
		text-align: start;
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
