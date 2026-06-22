/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// Shared scaffolding for the NewUserDialog / EditUserDialog specs. The mount
// factories stay per-spec (they differ in props, store getters, and mocks);
// only the version-agnostic stubs and helpers live here.

// The dialogs call the bare global `t`/`n` (injected at runtime by
// core/src/globals.js) inside <script>. Provide identity stand-ins on import.
globalThis.t = (_app: string, text: string) => text
globalThis.n = (_app: string, singular: string, plural: string, count: number) => (count === 1 ? singular : plural)

/**
 * Let pending microtasks (awaited promises) settle.
 *
 * Defined locally rather than imported from `@vue/test-utils`: the v1 (legacy
 * Vue 2) pipeline these specs also run under does not export flushPromises.
 */
export const flushPromises = () => new Promise((resolve) => setTimeout(resolve))

/** Stub that records the props NcDialog receives and renders both slots. */
export const NcDialogStub = {
	name: 'NcDialog',
	props: ['name', 'size', 'noClose', 'closeOnClickOutside', 'outTransition'],
	template: '<div><slot /><div data-test="actions"><slot name="actions" /></div></div>',
}

/** Stub that forwards attrs (e.g. aria-disabled) and renders icon + default slots. */
export const NcButtonStub = {
	name: 'NcButton',
	inheritAttrs: false,
	template: '<button v-bind="$attrs"><slot name="icon" /><slot /></button>',
}

/** Stub exposing focusField, which the dialogs call on mount and on errors. */
export const UserFormFieldsStub = {
	name: 'UserFormFields',
	methods: { focusField() {} },
	template: '<div />',
}
