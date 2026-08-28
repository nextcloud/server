/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// Shared scaffolding for the NewUserDialog / EditUserDialog specs. The mount
// factories stay per-spec (they differ in props, store getters, and mocks);
// only the version-agnostic stubs and helpers live here.
//
// The dialogs use `<script setup>`, so child components resolve from local
// imports and the store from `useStore()`. VTU's `stubs` and `mocks.$store`
// reach neither, so each spec swaps them with `vi.mock`. Stubs are render
// functions, not `template`: the runtime-only test build has no compiler.

// Minimal local typings so the stubs don't depend on a particular Vue version's
// type exports (the IDE resolves bare `vue` to the Vue 3 types).
type StubChild = unknown
type CreateElement = (tag: string, data?: Record<string, unknown> | StubChild[], children?: StubChild[]) => StubChild
interface StubVm {
	$attrs: Record<string, string>
	$scopedSlots: Record<string, ((props: object) => StubChild) | undefined>
}

// The dialogs translate via the imported `translate`, but the store and some
// helpers still call the bare globals. Provide identity stand-ins on import.
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
	render(this: StubVm, h: CreateElement) {
		return h('div', [
			this.$scopedSlots.default?.({}),
			h('div', { attrs: { 'data-test': 'actions' } }, [this.$scopedSlots.actions?.({})]),
		])
	},
}

/** Stub that forwards attrs (e.g. aria-disabled) and renders icon + default slots. */
export const NcButtonStub = {
	name: 'NcButton',
	inheritAttrs: false,
	render(this: StubVm, h: CreateElement) {
		return h('button', { attrs: this.$attrs }, [
			this.$scopedSlots.icon?.({}),
			this.$scopedSlots.default?.({}),
		])
	},
}

/** Stub exposing focusField, which the dialogs call on mount and on errors. */
export const UserFormFieldsStub = {
	name: 'UserFormFields',
	methods: { focusField() {} },
	render: (h: CreateElement) => h('div'),
}
