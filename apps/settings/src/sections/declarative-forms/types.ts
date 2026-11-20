/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** Option of a radio or multi-checkbox field */
export interface DeclarativeFormOption {
	name: string
	value: string
}

/** Field types rendered as a single line input */
export const TEXT_TYPES = ['text', 'password', 'email', 'tel', 'url', 'number'] as const

export type DeclarativeTextFieldType = typeof TEXT_TYPES[number]

/** Field of a declarative form, see `IDeclarativeSettingsForm` */
export interface DeclarativeFormField {
	id: string
	title: string
	description?: string
	type: DeclarativeTextFieldType | 'checkbox' | 'multi-checkbox' | 'radio' | 'select' | 'multi-select'
	placeholder?: string
	label?: string
	options?: string[] | DeclarativeFormOption[]
	sensitive?: boolean
	value: unknown
}

/** Declarative form with its current values, as provided by the server */
export interface DeclarativeForm {
	id: string
	app: string
	priority: number
	section_type: 'admin' | 'personal'
	section_id: string
	storage_type: 'internal' | 'external'
	title: string
	description?: string
	doc_url?: string
	fields: DeclarativeFormField[]
}
