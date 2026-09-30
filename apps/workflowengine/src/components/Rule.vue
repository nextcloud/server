<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div v-if="operation" :class="$style.rule" :style="{ borderInlineStartColor: operation.color || '' }">
		<div :class="$style.trigger">
			<p>
				<span>{{ t('workflowengine', 'When') }}</span>
				<Event :rule="rule" @update="updateRule" />
			</p>
			<p v-for="check in rule.checks" :key="checkKey(check)">
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
					:class="$style.addCheck"
					:value="t('workflowengine', 'Add a new filter')"
					type="button"
					@click="onAddFilter">
			</p>
		</div>
		<div class="icon-confirm" :class="[$style.flowIcon]" />
		<div :class="$style.action">
			<Operation :operation="operation">
				<!-- eslint-disable vue/attribute-hyphenation -- a custom element takes hyphenated attributes -->
				<component
					:is="operation.element"
					v-if="operation.element"
					ref="operationElement"
					:model-value="inputValue" />
				<!-- eslint-enable vue/attribute-hyphenation -->
			</Operation>
			<div :class="$style.buttons">
				<NcButton v-if="rule.id < -1 || dirty" @click="cancelRule">
					{{ t('workflowengine', 'Cancel') }}
				</NcButton>
				<NcButton v-else-if="!dirty" @click="deleteRule">
					{{ t('workflowengine', 'Delete') }}
				</NcButton>
				<NcButton
					:title="ruleStatus.tooltip"
					:variant="ruleStatus.variant"
					@click="saveRule">
					<template #icon>
						<NcIconSvgWrapper :path="ruleStatus.icon" :size="20" />
					</template>
					{{ ruleStatus.title }}
				</NcButton>
			</div>
			<p v-if="error" :class="$style.errorMessage">
				{{ error }}
			</p>
		</div>
	</div>
</template>

<script setup lang="ts">
/* eslint vue/multi-word-component-names: "warn" */

import type { Check as CheckType, Rule } from '../types.ts'

import { mdiArrowRight, mdiCheck, mdiClose } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed, onMounted, ref, useTemplateRef } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import Check from './Check.vue'
import Event from './Event.vue'
import Operation from './Operation.vue'
import { useCustomElementEvents } from '../composables/useCustomElementEvents.ts'
import { logger } from '../logger.ts'
import { useWorkflowStore } from '../store.ts'

const props = defineProps<{ rule: Rule }>()

const store = useWorkflowStore()

const error = ref<string | null>(null)
const dirty = ref(props.rule.id < 0)
const originalRule = ref<Rule | null>(null)
const inputValue = ref('')
const operationElement = useTemplateRef<Element>('operationElement')

const operation = computed(() => store.operationForRule(props.rule))

const ruleStatus = computed(() => {
	if (error.value || !props.rule.valid || props.rule.checks.length === 0
		|| props.rule.checks.some((check) => check.invalid === true)) {
		return {
			title: t('workflowengine', 'The configuration is invalid'),
			icon: mdiClose,
			variant: 'warning' as const,
			tooltip: error.value ?? undefined,
		}
	}
	if (!dirty.value) {
		return { title: t('workflowengine', 'Active'), icon: mdiCheck, variant: 'success' as const, tooltip: undefined }
	}
	return { title: t('workflowengine', 'Save'), icon: mdiArrowRight, variant: 'primary' as const, tooltip: undefined }
})

const lastCheckComplete = computed(() => {
	const lastCheck = props.rule.checks.at(-1)
	return lastCheck === undefined || lastCheck.class !== null
})

/**
 * Filter rows are keyed by the check they render, so removing one cannot make
 * another row reuse a component that still points at the removed check.
 *
 * @param check - The check of the row
 */
function checkKey(check: CheckType): CheckType {
	return check
}

function validate(): void {
	error.value = null
	store.updateRule(props.rule)
}

function updateRule(): void {
	dirty.value = true
	error.value = null
	store.updateRule(props.rule)
}

/**
 * @param event - The change the operation's custom element reported
 */
function updateOperationByEvent(event: CustomEvent<unknown[]>): void {
	inputValue.value = event.detail[0] as string
	store.setRuleOperation(props.rule, inputValue.value)
	updateRule()
}

async function saveRule(): Promise<void> {
	try {
		await store.pushUpdateRule(props.rule)
		dirty.value = false
		error.value = null
		originalRule.value = structuredClone(toPlainRule(props.rule))
	} catch (exception) {
		logger.error('Failed to save operation', { error: exception })
		error.value = ocsMessage(exception)
	}
}

async function deleteRule(): Promise<void> {
	try {
		await store.deleteRule(props.rule)
	} catch (exception) {
		logger.error('Failed to delete operation', { error: exception })
		error.value = ocsMessage(exception)
	}
}

function cancelRule(): void {
	if (props.rule.id < 0) {
		store.removeRule(props.rule)
		return
	}
	inputValue.value = originalRule.value!.operation
	store.updateRule(originalRule.value!)
	originalRule.value = structuredClone(toPlainRule(props.rule))
	dirty.value = false
}

/**
 * @param check - The filter to drop
 */
function removeCheck(check: CheckType): void {
	store.removeCheck(props.rule, check)
	updateRule()
}

function onAddFilter(): void {
	store.addCheck(props.rule)
}

/**
 * The store holds reactive proxies, which cannot be structurally cloned.
 *
 * @param rule - The rule to copy
 */
function toPlainRule(rule: Rule): Rule {
	return JSON.parse(JSON.stringify(rule))
}

/**
 * @param exception - The rejected request
 */
function ocsMessage(exception: unknown): string | null {
	const response = (exception as { response?: { data?: { ocs?: { meta?: { message?: string } } } } }).response
	return response?.data?.ocs?.meta?.message ?? null
}

useCustomElementEvents(operationElement, {
	'update:model-value': updateOperationByEvent,
})

onMounted(() => {
	originalRule.value = structuredClone(toPlainRule(props.rule))
	if (operation.value?.element) {
		inputValue.value = props.rule.operation
	}
})
</script>

<style module lang="scss">
.buttons {
	display: flex;
	justify-content: end;

	button {
		margin-inline-start: 5px;
	}

	button:last-child {
		margin-inline-end: 10px;
	}
}

.errorMessage {
	float: inline-end;
	margin-inline-end: 10px;
}

.flowIcon {
	width: 44px;
	background-position: right 27px;
	padding-inline-end: 20px;
	margin-inline-end: 20px;
}

.rule {
	display: flex;
	flex-wrap: wrap;
	border-inline-start: 5px solid var(--color-primary-element);
	margin-bottom: 20px;
	padding: 10px;
}

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

.trigger p,
.action p {
	min-height: 34px;
	display: flex;

	& > span:first-child {
		min-width: 50px;
		text-align: end;
		color: var(--color-text-maxcontrast);
		padding-inline-end: 10px;
		padding-top: 6px;
	}
}

.trigger p:first-child span {
	padding-top: 3px;
}

.trigger p:last-child {
	padding-top: 8px;
}

.addCheck {
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

@media (max-width: 1400px) {
	.rule {
		width: 100%;
		max-width: 100%;
	}

	.trigger,
	.action {
		width: 100%;
		max-width: 100%;
	}

	.flowIcon {
		display: none;
	}
}
</style>
