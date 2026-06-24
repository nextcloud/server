/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import Rule from './Rule.vue'

type StubInstance = { label: string, value: string, $emit: (event: string, payload: string) => void }

type RuleData = {
	id: number
	class: string
	entity: string
	events: string[]
	name: string
	description: string
	checks: Array<{ class: string | null, operator: string | null, value: string, invalid?: boolean }>
	operation: string
	valid: boolean
}

const NEW_RULE_ID = 0

function makeRule(overrides: Partial<RuleData> = {}): RuleData {
	return {
		id: 42,
		class: 'OCA\\WFE\\TestOp',
		entity: 'WorkflowEngine_Entity_File',
		events: ['file:created'],
		name: '',
		description: '',
		checks: [{ class: 'OCA\\WFE\\Check', operator: 'is', value: 'foo' }],
		operation: 'do-something',
		valid: true,
		...overrides,
	}
}

function makeStore(overrides: { dispatch?: ReturnType<typeof vi.fn> } = {}) {
	const dispatch = overrides.dispatch ?? vi.fn().mockResolvedValue(undefined)
	return {
		dispatch,
		getters: {
			getOperationForRule: () => ({
				id: 'OCA\\WFE\\TestOp',
				color: '',
				element: {
					render(h: (tag: string, data?: Record<string, unknown>) => unknown) {
						return h('div', { attrs: { 'data-test': 'operation-element' } })
					},
				},
			}),
		},
	}
}

function mountRule(rule: RuleData, dispatch?: ReturnType<typeof vi.fn>) {
	const store = makeStore({ dispatch })
	const wrapper = mount(Rule, {
		propsData: { rule },
		mocks: {
			t: (_app: string, text: string) => text,
			$store: store,
		},
		stubs: {
			NcButton: true,
			NcActionButton: true,
			NcActions: true,
			NcTextField: {
				props: ['modelValue', 'label', 'maxlength'],
				render(this: StubInstance, h) {
					return h('input', {
						attrs: { 'aria-label': this.label, value: this.value },
						class: 'nc-text-field-stub',
						on: { input: (e: Event) => this.$emit('update:modelValue', (e.target as HTMLInputElement).value) },
					})
				},
			},
			NcTextArea: {
				props: ['modelValue', 'label'],
				render(this: StubInstance, h) {
					return h('textarea', {
						attrs: { 'aria-label': this.label, value: this.value },
						class: 'nc-text-area-stub',
						on: { input: (e: Event) => this.$emit('update:modelValue', (e.target as HTMLTextAreaElement).value) },
					})
				},
			},
			Check: true,
			Event: true,
			Operation: true,
			MenuDown: true,
			MenuUp: true,
		},
	})
	return { wrapper, store }
}

describe('Rule.vue', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it('renders collapsed for a saved rule', () => {
		const { wrapper } = mountRule(makeRule({ id: 42, name: 'Tag PDFs', description: 'Adds the pdf tag\nApplies on upload' }))

		const details = wrapper.find('details')
		expect(details.exists()).toBe(true)
		expect((wrapper.vm as any).expanded).toBe(false)
		expect(details.attributes('open')).toBeUndefined()
	})

	it('auto-expands a new unsaved rule', () => {
		const { wrapper } = mountRule(makeRule({ id: NEW_RULE_ID }))

		expect((wrapper.vm as any).expanded).toBe(true)
		expect(wrapper.find('details').attributes('open')).toBeDefined()
	})

	it('shows the rule name in the collapsed header', () => {
		const { wrapper } = mountRule(makeRule({ id: 42, name: 'Tag PDFs', description: 'Adds the pdf tag\nApplies on upload' }))

		expect(wrapper.find('.rule__header__title').text()).toBe('Tag PDFs')
	})

	it('falls back to "Unnamed flow" when the name is empty', () => {
		const { wrapper } = mountRule(makeRule({ id: 42, name: '', description: '' }))

		expect(wrapper.find('.rule__header__title').text()).toBe('Unnamed flow')
	})

	it('toggles expansion on summary click', async () => {
		const { wrapper } = mountRule(makeRule({ id: 42 }))

		expect((wrapper.vm as any).expanded).toBe(false)

		// Simulate the browser toggle event (jsdom fires it when summary is clicked)
		const details = wrapper.find('details').element as HTMLDetailsElement
		details.open = true
		await wrapper.find('details').trigger('toggle')

		expect((wrapper.vm as any).expanded).toBe(true)

		details.open = false
		await wrapper.find('details').trigger('toggle')

		expect((wrapper.vm as any).expanded).toBe(false)
	})

	it('shows the cancel button for a new unsaved rule', () => {
		const { wrapper } = mountRule(makeRule({ id: NEW_RULE_ID }))

		expect(wrapper.html()).toContain('Cancel')
		expect(wrapper.html()).not.toContain('Delete')
	})

	it('shows the delete button for a saved clean rule', () => {
		const { wrapper } = mountRule(makeRule({ id: 42 }))

		expect(wrapper.html()).not.toContain('Cancel')
		expect(wrapper.html()).toContain('Delete')
	})

	it('name and description are optional and can be empty', async () => {
		const dispatch = vi.fn().mockResolvedValue(undefined)
		const rule = makeRule({ id: 42, name: 'Tag PDFs', description: 'Some desc' })
		const { wrapper } = mountRule(rule, dispatch)

		const nameInput = wrapper.find('.nc-text-field-stub')
		const nameEl = nameInput.element as HTMLInputElement
		nameEl.value = ''
		await nameInput.trigger('input')
		expect(dispatch).toHaveBeenCalledWith('updateRule', expect.objectContaining({ name: '' }))

		const descArea = wrapper.find('.nc-text-area-stub')
		const descEl = descArea.element as HTMLTextAreaElement
		descEl.value = ''
		await descArea.trigger('input')
		expect(dispatch).toHaveBeenCalledWith('updateRule', expect.objectContaining({ description: '' }))
	})

	it('editing the name marks the rule dirty', async () => {
		const dispatch = vi.fn().mockResolvedValue(undefined)
		const rule = makeRule({ id: 42, name: '', description: '' })
		const { wrapper } = mountRule(rule, dispatch)

		expect((wrapper.vm as any).dirty).toBe(false)

		const nameInput = wrapper.find('.nc-text-field-stub')
		expect(nameInput.exists()).toBe(true)
		;(nameInput.element as HTMLInputElement).value = 'Tag PDFs'
		await nameInput.trigger('input')

		expect(rule.name).toBe('') // prop is not mutated directly anymore
		expect((wrapper.vm as any).dirty).toBe(true)
		expect(dispatch).toHaveBeenCalledWith('updateRule', { ...rule, name: 'Tag PDFs' })
	})

	it('editing the description marks the rule dirty', async () => {
		const dispatch = vi.fn().mockResolvedValue(undefined)
		const rule = makeRule({ id: 42, name: '', description: '' })
		const { wrapper } = mountRule(rule, dispatch)

		expect((wrapper.vm as any).dirty).toBe(false)

		const descArea = wrapper.find('.nc-text-area-stub')
		expect(descArea.exists()).toBe(true)
		;(descArea.element as HTMLTextAreaElement).value = 'New description'
		await descArea.trigger('input')

		expect(rule.description).toBe('') // prop is not mutated directly anymore
		expect((wrapper.vm as any).dirty).toBe(true)
		expect(dispatch).toHaveBeenCalledWith('updateRule', { ...rule, description: 'New description' })
	})
})
