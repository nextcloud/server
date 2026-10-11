/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type {
	Check,
	CheckPlugin,
	Entity,
	FlatEntityEvent,
	OperatorPlugin,
	Rule,
	ScopeValue,
	ServerCheck,
} from './types.ts'

import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { getApiUrl } from './helpers/api.ts'

const DEFAULT_OPERATOR_COLOR = 'var(--color-primary-element)'

interface OcsResponse<T> {
	ocs: { data: T }
}

/**
 * Flatten the events of every entity into one list, so a trigger can be picked
 * across entities.
 *
 * @param entities - The entities the server advertises
 */
function flattenEvents(entities: Entity[]): FlatEntityEvent[] {
	return entities.flatMap((entity) => entity.events.map((event) => ({
		id: `${entity.id}::${event.eventName}`,
		entity,
		...event,
	})))
}

export const useWorkflowStore = defineStore('workflowengine', () => {
	const scope = loadState<ScopeValue>('workflowengine', 'scope')
	const appstoreEnabled = loadState<boolean>('workflowengine', 'appstoreenabled')
	const entities = loadState<Entity[]>('workflowengine', 'entities')
	const events = flattenEvents(entities)
	const checks = loadState<Record<string, ServerCheck>>('workflowengine', 'checks')

	const rules = ref<Rule[]>([])
	/** Operations the server knows, enriched by the plugins apps register. */
	const operations = ref<Record<string, OperatorPlugin>>(loadState<Record<string, OperatorPlugin>>('workflowengine', 'operators'))
	/** Check plugins apps register, by check class. */
	const pluginChecks = ref<Record<string, CheckPlugin>>({})

	/** The flows to display: those whose operation this instance still knows. */
	const configuredRules = computed(() => rules.value
		.filter((rule) => operations.value[rule.class] !== undefined)
		.sort((first, second) => first.id - second.id))

	/**
	 * The operation a flow runs.
	 *
	 * @param rule - The flow to look up
	 */
	function operationForRule(rule: Rule): OperatorPlugin | undefined {
		return operations.value[rule.class]
	}

	/**
	 * The entity an operation is fixed to.
	 *
	 * @param operation - The operation to look up
	 */
	function entityForOperation(operation: OperatorPlugin): Entity | undefined {
		return entities.find((entity) => operation.fixedEntity === entity.id)
	}

	/**
	 * The registered check plugins that apply to an entity, by check class.
	 * A check without supported entities applies to all of them.
	 *
	 * @param entity - Class of the entity to filter by
	 */
	function checksForEntity(entity: string): Record<string, CheckPlugin> {
		return Object.values(checks)
			.filter((check) => check.supportedEntities.length === 0 || check.supportedEntities.includes(entity))
			.map((check) => pluginChecks.value[check.id])
			.reduce<Record<string, CheckPlugin>>((available, plugin) => {
				available[plugin.class] = plugin
				return available
			}, {})
	}

	/**
	 * Register the value editor of a check.
	 *
	 * @param plugin - The plugin to register
	 */
	function addPluginCheck(plugin: CheckPlugin): void {
		pluginChecks.value = { ...pluginChecks.value, [plugin.class]: plugin }
	}

	/**
	 * Enrich a server operation with the plugin an app registered for it.
	 * Plugins for operations this instance does not have are ignored.
	 *
	 * @param plugin - The plugin to register
	 */
	function addPluginOperator(plugin: OperatorPlugin): void {
		const operation = operations.value[plugin.id]
		if (operation === undefined) {
			return
		}
		// the server data wins over the plugin, the default only fills a gap
		const merged = { ...plugin, ...operation }
		operations.value = {
			...operations.value,
			[plugin.id]: { ...merged, color: merged.color ?? DEFAULT_OPERATOR_COLOR },
		}
	}

	/**
	 * Replace a flow in place, keeping the order of the list.
	 *
	 * @param rule - The flow to store
	 */
	function updateRule(rule: Rule): void {
		const index = rules.value.findIndex((item) => item.id === rule.id)
		if (index === -1) {
			return
		}
		const stored = {
			...rule,
			events: typeof rule.events === 'string' ? JSON.parse(rule.events) : rule.events,
		}
		rules.value.splice(index, 1, stored)
	}

	/**
	 * Apply a change to a check. Checks are edited in place, so this is the one
	 * place that writes to them; persisting the flow stays with the caller.
	 *
	 * @param check - The check to change
	 * @param patch - The fields to change
	 */
	function updateCheck(check: Check, patch: Partial<Check>): void {
		Object.assign(check, patch)
	}

	/**
	 * Append an empty check to a flow.
	 *
	 * @param rule - The flow to extend
	 */
	function addCheck(rule: Rule): void {
		rule.checks.push({ class: null, operator: null, value: '' })
	}

	/**
	 * Drop a check from a flow.
	 *
	 * @param rule - The flow to shorten
	 * @param check - The check to drop
	 */
	function removeCheck(rule: Rule, check: Check): void {
		const index = rule.checks.indexOf(check)
		if (index > -1) {
			rule.checks.splice(index, 1)
		}
	}

	/**
	 * Point a flow at the entity it watches and the events that trigger it.
	 *
	 * @param rule - The flow to retrigger
	 * @param entity - Class of the entity to watch
	 * @param events - Names of the events to react to
	 */
	function setRuleTrigger(rule: Rule, entity: string, events: string[]): void {
		rule.entity = entity
		rule.events = events
	}

	/**
	 * Store the value the operation's own editor produced.
	 *
	 * @param rule - The flow to change
	 * @param operation - The new operation value
	 */
	function setRuleOperation(rule: Rule, operation: string): void {
		rule.operation = operation
	}

	/**
	 * Drop a flow from the list without deleting it on the server.
	 *
	 * @param rule - The flow to drop
	 */
	function removeRule(rule: Rule): void {
		const index = rules.value.findIndex((item) => item.id === rule.id)
		if (index === -1) {
			return
		}
		rules.value.splice(index, 1)
	}

	/** Load the configured flows of this scope. */
	async function fetchRules(): Promise<void> {
		const { data } = await axios.get<OcsResponse<Record<string, Rule[]>>>(getApiUrl(scope))
		rules.value.push(...Object.values(data.ocs.data).flat().map((rule) => ({ ...rule, valid: true })))
	}

	/**
	 * Start a new, unsaved flow for an operation. Unsaved flows are told apart
	 * by their negative id.
	 *
	 * @param operation - The operation the flow should run
	 */
	async function createNewRule(operation: OperatorPlugin): Promise<void> {
		await confirmPassword()

		let entity: Entity | undefined
		let firstEvent: string[] = []
		if (operation.isComplex === false && operation.fixedEntity === '') {
			entity = entities.find((item) => operation.entities && operation.entities[0] === item.id) ?? entities[0]
			firstEvent = [entity.events[0].eventName]
		}

		rules.value.push({
			id: -Date.now(),
			class: operation.id,
			entity: entity ? entity.id : operation.fixedEntity,
			events: firstEvent,
			name: '', // unused in the new ui, there for legacy reasons
			checks: [{ class: null, operator: null, value: '' }],
			operation: operation.operation || '',
			valid: true,
		})
	}

	/**
	 * Persist a flow, creating it when it has not been saved before.
	 *
	 * @param rule - The flow to persist
	 */
	async function pushUpdateRule(rule: Rule): Promise<void> {
		await confirmPassword()
		const { data } = rule.id < 0
			? await axios.post<OcsResponse<Rule>>(getApiUrl(scope), rule)
			: await axios.put<OcsResponse<Rule>>(getApiUrl(scope, `/${rule.id}`), rule)
		rule.id = data.ocs.data.id
		updateRule(rule)
	}

	/**
	 * Delete a flow on the server and drop it from the list.
	 *
	 * @param rule - The flow to delete
	 */
	async function deleteRule(rule: Rule): Promise<void> {
		await confirmPassword()
		await axios.delete(getApiUrl(scope, `/${rule.id}`))
		removeRule(rule)
	}

	/**
	 * Record whether a flow satisfies its checks.
	 *
	 * @param rule - The flow to mark
	 * @param valid - Whether it is valid
	 */
	function setValid(rule: Rule, valid: boolean): void {
		rule.valid = valid
		updateRule(rule)
	}

	return {
		scope,
		appstoreEnabled,
		entities,
		events,
		checks,
		rules,
		operations,
		pluginChecks,

		configuredRules,
		operationForRule,
		entityForOperation,
		checksForEntity,

		addPluginCheck,
		addPluginOperator,
		fetchRules,
		createNewRule,
		updateRule,
		removeRule,
		updateCheck,
		addCheck,
		removeCheck,
		setRuleTrigger,
		setRuleOperation,
		pushUpdateRule,
		deleteRule,
		setValid,
	}
})
