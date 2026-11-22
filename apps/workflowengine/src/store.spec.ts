/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { CheckPlugin, Entity, OperatorPlugin, Rule, ServerCheck } from './types.ts'

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const BLOCK_VERSIONING = 'OCA\\Files_Versions\\BlockVersioningOperation'
const FILE_NAME_CHECK = 'OCA\\WorkflowEngine\\Check\\FileName'
const REQUEST_URL_CHECK = 'OCA\\WorkflowEngine\\Check\\RequestURL'

const entities: Entity[] = [{
	id: 'OCA\\WorkflowEngine\\Entity\\File',
	icon: 'file.svg',
	name: 'File',
	events: [{ eventName: 'postWrite', displayName: 'File created' }],
}]

const serverChecks: Record<string, ServerCheck> = {
	[FILE_NAME_CHECK]: { id: FILE_NAME_CHECK, supportedEntities: ['OCA\\WorkflowEngine\\Entity\\File'], supportedEvents: [] },
	[REQUEST_URL_CHECK]: { id: REQUEST_URL_CHECK, supportedEntities: [], supportedEvents: [] },
}

const serverOperators: Record<string, Partial<OperatorPlugin>> = {
	[BLOCK_VERSIONING]: {
		id: BLOCK_VERSIONING,
		name: 'Block file versioning',
		description: 'Automatic tag based blocking',
		fixedEntity: 'OCA\\WorkflowEngine\\Entity\\File',
		isComplex: true,
	},
}

const initialState: Record<string, unknown> = {
	scope: 0,
	appstoreenabled: false,
	entities,
	checks: serverChecks,
	operators: serverOperators,
}

vi.mock('@nextcloud/initial-state', () => ({
	loadState: (app: string, key: string) => structuredClone(initialState[key]),
}))

const confirmPassword = vi.fn(() => Promise.resolve())
vi.mock('@nextcloud/password-confirmation', () => ({
	confirmPassword: () => confirmPassword(),
}))

const axios = {
	get: vi.fn(),
	post: vi.fn(),
	put: vi.fn(),
	delete: vi.fn(),
}
vi.mock('@nextcloud/axios', () => ({ default: axios }))

const { useWorkflowStore } = await import('./store.ts')

/**
 * @param overrides - Fields to change on the default rule
 */
function rule(overrides: Partial<Rule> = {}): Rule {
	return {
		id: 1,
		class: BLOCK_VERSIONING,
		name: '',
		entity: 'OCA\\WorkflowEngine\\Entity\\File',
		events: [],
		operation: 'deny',
		checks: [{ class: FILE_NAME_CHECK, operator: 'is', value: 'a.txt' }],
		...overrides,
	}
}

beforeEach(() => {
	setActivePinia(createPinia())
	vi.clearAllMocks()
})

describe('plugin registration', () => {
	it('keeps a check plugin by its class', () => {
		const store = useWorkflowStore()
		const plugin: CheckPlugin = { class: FILE_NAME_CHECK, name: 'File name', operators: [] }

		store.addPluginCheck(plugin)

		expect(store.pluginChecks[FILE_NAME_CHECK]).toEqual(plugin)
	})

	it('lets the server data win over the plugin', () => {
		const store = useWorkflowStore()

		store.addPluginOperator({ id: BLOCK_VERSIONING, name: 'Overridden', color: '#ff5900' } as OperatorPlugin)

		expect(store.operations[BLOCK_VERSIONING].name).toBe('Block file versioning')
		expect(store.operations[BLOCK_VERSIONING].color).toBe('#ff5900')
	})

	it('gives an operator without a colour the primary element', () => {
		const store = useWorkflowStore()

		store.addPluginOperator({ id: BLOCK_VERSIONING, operation: 'deny' } as OperatorPlugin)

		expect(store.operations[BLOCK_VERSIONING].color).toBe('var(--color-primary-element)')
	})

	it('ignores a plugin for an operation this instance does not have', () => {
		const store = useWorkflowStore()

		store.addPluginOperator({ id: 'OCA\\Other\\Operation', color: '#000' } as OperatorPlugin)

		expect(store.operations['OCA\\Other\\Operation']).toBeUndefined()
	})
})

describe('getters', () => {
	it('lists only flows whose operation is still installed, by id', () => {
		const store = useWorkflowStore()
		store.rules.push(rule({ id: 3 }), rule({ id: 2, class: 'OCA\\Gone\\Operation' }), rule({ id: 1 }))

		expect(store.configuredRules.map((item) => item.id)).toEqual([1, 3])
	})

	it('finds the operation and the entity of a flow', () => {
		const store = useWorkflowStore()
		const operation = store.operationForRule(rule())

		expect(operation?.name).toBe('Block file versioning')
		expect(store.entityForOperation(operation!)?.name).toBe('File')
	})

	it('offers a check that names the entity and one that names none', () => {
		const store = useWorkflowStore()
		store.addPluginCheck({ class: FILE_NAME_CHECK, name: 'File name', operators: [] })
		store.addPluginCheck({ class: REQUEST_URL_CHECK, name: 'Request URL', operators: [] })

		const available = store.checksForEntity('OCA\\WorkflowEngine\\Entity\\File')

		expect(Object.keys(available)).toEqual([FILE_NAME_CHECK, REQUEST_URL_CHECK])
	})

	it('drops a check that names another entity', () => {
		const store = useWorkflowStore()
		store.addPluginCheck({ class: FILE_NAME_CHECK, name: 'File name', operators: [] })
		store.addPluginCheck({ class: REQUEST_URL_CHECK, name: 'Request URL', operators: [] })

		const available = store.checksForEntity('OCA\\WorkflowEngine\\Entity\\Other')

		expect(Object.keys(available)).toEqual([REQUEST_URL_CHECK])
	})
})

describe('loading and creating flows', () => {
	it('loads the flows of its scope and marks them valid', async () => {
		axios.get.mockResolvedValue({ data: { ocs: { data: { [BLOCK_VERSIONING]: [rule({ id: 4 })] } } } })
		const store = useWorkflowStore()

		await store.fetchRules()

		expect(axios.get).toHaveBeenCalledWith(expect.stringContaining('workflows/global'))
		expect(store.rules).toHaveLength(1)
		expect(store.rules[0].valid).toBe(true)
	})

	it('starts an unsaved flow with one empty check', async () => {
		const store = useWorkflowStore()

		await store.createNewRule(store.operations[BLOCK_VERSIONING])

		expect(confirmPassword).toHaveBeenCalled()
		expect(store.rules).toHaveLength(1)
		expect(store.rules[0].id).toBeLessThan(0)
		expect(store.rules[0].checks).toEqual([{ class: null, operator: null, value: '' }])
	})
})

describe('persisting flows', () => {
	it('creates a flow that was never saved and adopts its id', async () => {
		axios.post.mockResolvedValue({ data: { ocs: { data: { id: 42 } } } })
		const store = useWorkflowStore()
		const unsaved = rule({ id: -1 })
		store.rules.push(unsaved)

		await store.pushUpdateRule(store.rules[0])

		expect(axios.post).toHaveBeenCalled()
		expect(axios.put).not.toHaveBeenCalled()
		expect(store.rules[0].id).toBe(42)
	})

	it('updates a flow that was saved before', async () => {
		axios.put.mockResolvedValue({ data: { ocs: { data: { id: 7 } } } })
		const store = useWorkflowStore()
		store.rules.push(rule({ id: 7 }))

		await store.pushUpdateRule(store.rules[0])

		expect(axios.put).toHaveBeenCalledWith(expect.stringContaining('/7'), expect.anything())
		expect(axios.post).not.toHaveBeenCalled()
	})

	it('deletes a flow and drops it from the list', async () => {
		axios.delete.mockResolvedValue({})
		const store = useWorkflowStore()
		store.rules.push(rule({ id: 7 }))

		await store.deleteRule(store.rules[0])

		expect(axios.delete).toHaveBeenCalledWith(expect.stringContaining('/7'))
		expect(store.rules).toHaveLength(0)
	})

	it('leaves the list alone for a flow it does not hold', () => {
		const store = useWorkflowStore()
		store.rules.push(rule({ id: 7 }))

		store.updateRule(rule({ id: 999, operation: 'changed' }))
		store.removeRule(rule({ id: 999 }))

		expect(store.rules).toHaveLength(1)
		expect(store.rules[0].operation).toBe('deny')
	})
})

describe('editing a flow', () => {
	it('changes a check in place', () => {
		const store = useWorkflowStore()
		store.rules.push(rule())

		store.updateCheck(store.rules[0].checks[0], { operator: 'matches', value: '/^a/' })

		expect(store.rules[0].checks[0]).toMatchObject({ operator: 'matches', value: '/^a/' })
	})

	it('appends and drops checks', () => {
		const store = useWorkflowStore()
		store.rules.push(rule())
		const first = store.rules[0].checks[0]

		store.addCheck(store.rules[0])
		expect(store.rules[0].checks).toHaveLength(2)

		store.removeCheck(store.rules[0], first)
		expect(store.rules[0].checks).toHaveLength(1)
		expect(store.rules[0].checks[0].class).toBeNull()
	})

	it('points a flow at another entity and its events', () => {
		const store = useWorkflowStore()
		store.rules.push(rule())

		store.setRuleTrigger(store.rules[0], 'OCA\\WorkflowEngine\\Entity\\Other', ['postDelete'])

		expect(store.rules[0].entity).toBe('OCA\\WorkflowEngine\\Entity\\Other')
		expect(store.rules[0].events).toEqual(['postDelete'])
	})

	it('records the value of the operation editor', () => {
		const store = useWorkflowStore()
		store.rules.push(rule())

		store.setRuleOperation(store.rules[0], 'allow')

		expect(store.rules[0].operation).toBe('allow')
	})
})
