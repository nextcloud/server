<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<details
		v-if="operation"
		:open="expanded"
		class="section rule"
		:style="{ borderLeftColor: operation.color || '' }"
		@toggle="expanded = $event.target.open">
		<summary class="rule__header">
			<span class="rule__header__status">
				<component :is="ruleStatus.icon" :size="20" />
			</span>
			<span class="rule__header__title">{{ displayName }}</span>
			<span class="rule__header__chevron">
				<component :is="expanded ? 'MenuUp' : 'MenuDown'" :size="20" />
			</span>
		</summary>
		<div class="rule__body">
			<div class="rule__meta">
				<NcTextField
					class="rule__meta__field"
					:label="t('workflowengine', 'Name')"
					:model-value="rule.name || ''"
					:maxlength="256"
					@update:modelValue="updateName" />
				<NcTextArea
					class="rule__meta__field"
					:label="t('workflowengine', 'Description')"
					:model-value="rule.description || ''"
					@update:modelValue="updateDescription" />
			</div>
			<div class="rule__editor">
				<div class="trigger">
					<p>
						<span>{{ t('workflowengine', 'When') }}</span>
						<Event :rule="rule" @update="updateRule" />
					</p>
					<p v-for="(check, index) in rule.checks" :key="index">
						<span>{{ t('workflowengine', 'and') }}</span>
						<Check
							:check="check"
							:rule="rule"
							@update="updateRule"
							@validate="validate"
							@remove="removeCheck(check)" />
					</p>
					<p>
						<span />
						<input
							v-if="lastCheckComplete"
							type="button"
							class="check--add"
							:value="t('workflowengine', 'Add a new filter')"
							@click="onAddFilter">
					</p>
				</div>
				<div class="flow-icon icon-confirm" />
				<div class="action">
					<Operation :operation="operation">
						<component
							:is="operation.element"
							v-if="operation.element"
							:model-value="inputValue"
							@update:model-value="updateOperationByEvent" />
						<component
							:is="operation.options"
							v-else-if="operation.options"
							:value="rule.operation"
							@input="updateOperation" />
					</Operation>
					<div class="buttons">
						<NcButton v-if="rule.id < 1 || dirty" @click="cancelRule">
							{{ t('workflowengine', 'Cancel') }}
						</NcButton>
						<NcButton v-else-if="!dirty" @click="deleteRule">
							{{ t('workflowengine', 'Delete') }}
						</NcButton>
						<NcButton
							:type="ruleStatus.type"
							:title="ruleStatus.tooltip"
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
		</div>
	</details>
</template>

<script>
import { t } from '@nextcloud/l10n'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcTextArea from '@nextcloud/vue/components/NcTextArea'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import IconArrowRight from 'vue-material-design-icons/ArrowRight.vue'
import IconCheckMark from 'vue-material-design-icons/Check.vue'
import IconClose from 'vue-material-design-icons/Close.vue'
import MenuDown from 'vue-material-design-icons/MenuDown.vue'
import MenuUp from 'vue-material-design-icons/MenuUp.vue'
import Check from './Check.vue'
import Event from './Event.vue'
import Operation from './Operation.vue'
import { logger } from '../logger.ts'

export default {
	name: 'FlowRule',
	components: {
		Check,
		Event,
		MenuDown,
		MenuUp,
		NcActionButton,
		NcActions,
		NcButton,
		NcTextArea,
		NcTextField,
		Operation,
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
			dirty: this.rule.id < 1,
			originalRule: null,
			element: null,
			inputValue: '',
			expanded: this.rule.id < 1,
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
					tooltip: this.error,
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

		displayName() {
			const name = (this.rule.name || '').trim()
			return name || t('workflowengine', 'Unnamed flow')
		},
	},

	mounted() {
		this.originalRule = JSON.parse(JSON.stringify(this.rule))
		if (this.operation?.element) {
			this.inputValue = this.rule.operation
		} else if (this.operation?.options) {
			// keeping this in an else for apps that try to be backwards compatible and may ship both
			// to be removed in 03/2028
			logger.warn('Developer warning: `OperatorPlugin.options` is deprecated. Use `OperatorPlugin.element` instead.')
		}
	},

	methods: {
		updateName(value) {
			this._applyUpdate({ name: value })
		},

		updateDescription(value) {
			this._applyUpdate({ description: value })
		},

		async updateOperation(operation) {
			this._applyUpdate({ operation })
		},

		async updateOperationByEvent(event) {
			this.inputValue = event.detail[0]
			this._applyUpdate({ operation: event.detail[0] })
		},

		validate(/* state */) {
			this.error = null
			this.$store.dispatch('updateRule', this.rule)
		},

		// Called by child components (Check, Event) after they've updated the rule
		updateRule() {
			if (!this.dirty) {
				this.dirty = true
			}
			this.error = null
			this.$store.dispatch('updateRule', this.rule)
		},

		_applyUpdate(changes) {
			if (!this.dirty) {
				this.dirty = true
			}
			this.error = null
			this.$store.dispatch('updateRule', { ...this.rule, ...changes })
		},

		async saveRule() {
			try {
				await this.$store.dispatch('pushUpdateRule', this.rule)
				this.dirty = false
				this.expanded = false
				this.error = null
				this.originalRule = JSON.parse(JSON.stringify(this.rule))
			} catch (error) {
				logger.error('Failed to save operation', { error })
				this.error = error.response.data.ocs.meta.message
			}
		},

		async deleteRule() {
			try {
				await this.$store.dispatch('deleteRule', this.rule)
			} catch (error) {
				logger.error('Failed to delete operation', { error })
				this.error = error.response.data.ocs.meta.message
			}
		},

		cancelRule() {
			if (this.rule.id < 1) {
				this.$store.dispatch('removeRule', this.rule)
			} else {
				this.inputValue = this.originalRule.operation
				this.$store.dispatch('updateRule', this.originalRule)
				this.originalRule = JSON.parse(JSON.stringify(this.rule))
				this.dirty = false
				this.expanded = false
			}
		},

		async removeCheck(check) {
			this._applyUpdate({ checks: this.rule.checks.filter((item) => item !== check) })
		},

		onAddFilter() {
			this._applyUpdate({ checks: [...this.rule.checks, { class: null, operator: null, value: '' }] })
		},
	},
}
</script>

<style scoped lang="scss">

	.rule {
		display: flex;
		flex-direction: column;
		border-inline-start: 5px solid var(--color-primary-element);
		padding: 10px;
	}

	summary.rule__header {
		display: flex;
		align-items: center;
		gap: calc(2 * var(--default-grid-baseline, 4px));
		width: 100%;
		padding: calc(2 * var(--default-grid-baseline, 4px));
		border-bottom: 1px solid transparent;
		text-align: start;
		cursor: pointer;
		color: inherit;
		font: inherit;
		list-style: none;

		&::-webkit-details-marker {
			display: none;
		}

		&:hover,
		&:focus-visible {
			background-color: var(--color-background-hover);
		}

		&:focus-visible {
			outline: 2px solid var(--color-primary-element);
			outline-offset: -2px;
		}
	}

	.rule__header__status {
		display: inline-flex;
		flex-shrink: 0;
	}

	.rule__header__title {
		font-weight: bold;
		flex-shrink: 0;
		max-width: 40%;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	.rule__header__chevron {
		display: inline-flex;
		flex-shrink: 0;
		color: var(--color-text-maxcontrast);
	}

	.rule__body {
		display: flex;
		flex-direction: column;
		gap: calc(2 * var(--default-grid-baseline, 4px));
		padding: calc(2 * var(--default-grid-baseline, 4px));
		border-top: 1px solid var(--color-border);
	}

	.rule__meta {
		display: flex;
		flex-direction: column;
		gap: calc(2 * var(--default-grid-baseline, 4px));
	}

	.rule__meta__field {
		width: 100%;
	}

	.rule__editor {
		display: flex;
		flex-wrap: wrap;

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
		float: inline-end;
		margin-inline-end: 10px;
	}

	.flow-icon {
		width: 44px;
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
		.rule__editor {
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
