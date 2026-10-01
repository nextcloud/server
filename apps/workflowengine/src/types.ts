/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** Whether a flow applies to the whole instance or to a single account. */
export const Scope = {
	ADMIN: 0,
	PERSONAL: 1,
} as const

export type ScopeValue = typeof Scope[keyof typeof Scope]

/** A comparison a check offers, e.g. "is" or "does not match". */
export interface Comparison {
	/** Value the comparison is stored as, e.g. `!less` */
	operator: string
	/** Translated readable text, e.g. "less or equals" */
	name: string
}

/** One condition of a flow. */
export interface Check {
	/** PHP class of the check, `null` while the row is still empty */
	class: string | null
	operator: string | null
	value: string
	/** Set by the check editor when the value does not satisfy the check */
	invalid?: boolean
}

/** A configured flow. Unsaved flows carry a negative id. */
export interface Rule {
	id: number
	/** PHP class of the operation this flow runs */
	class: string
	name: string
	entity: string
	events: string[]
	operation: string
	checks: Check[]
	valid?: boolean
}

/** A check as the server advertises it. */
export interface ServerCheck {
	id: string
	/** Empty means the check applies to every entity */
	supportedEntities: string[]
	supportedEvents: string[]
}

/** An event an entity can emit. */
export interface EntityEvent {
	eventName: string
	displayName: string
}

/** An entity flows can be triggered by, e.g. a file. */
export interface Entity {
	id: string
	icon: string
	name: string
	events: EntityEvent[]
}

/** An entity event, flattened with the entity it belongs to. */
export interface FlatEntityEvent extends EntityEvent {
	id: string
	entity: Entity
}

/**
 * A plugin that provides the value editor of a check.
 *
 * Registered through `window.OCA.WorkflowEngine.registerCheck()`.
 */
export interface CheckPlugin {
	/** PHP class name of the check */
	class: string
	name: string
	/** Either a fixed list or a function deriving it from the current check */
	operators: Comparison[] | ((check: Check) => Comparison[])
	/** Placeholder for the plain value input, when no element is provided */
	placeholder?: (check: Check) => string
	/** Validates the value, when no element is provided */
	validate?: (check: Check) => boolean
	/**
	 * Id of a custom element as passed to `window.customElements.define()`,
	 * prefixed with the app namespace. It receives `model-value`, `operator` and
	 * `disabled`, and emits `update:model-value`, `valid` and `invalid`.
	 */
	element?: string
}

/** What the card of an operation shows. */
export interface OperationCard {
	name: string
	description: string
	icon: string
	iconClass?: string
	/** Colour of the card, defaults to the primary element colour */
	color?: string
}

/**
 * A plugin that describes an operation on the flow settings page.
 *
 * Registered through `window.OCA.WorkflowEngine.registerOperator()`.
 */
export interface OperatorPlugin extends OperationCard {
	/** PHP class name of the operation */
	id: string
	/** Default value of the operation field */
	operation: string
	fixedEntity: string
	isComplex: boolean
	triggerHint: string
	entities?: string[]
	/**
	 * Id of a custom element as passed to `window.customElements.define()`,
	 * prefixed with the app namespace. It receives `model-value` and emits
	 * `update:model-value`.
	 */
	element?: string
}

/** One of the values a check offers besides free text. */
export interface PredefinedValue {
	/** The value that gets stored */
	id: string
	label: string
	/** A global icon class */
	icon?: string
	/** An image to show instead of an icon class */
	iconUrl?: string
}

/** The api apps register their plugins through. */
export interface WorkflowEngineApi {
	registerCheck(plugin: CheckPlugin): void
	registerOperator(plugin: OperatorPlugin): void
}
