/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// Shared scaffolding for the NewUserDialog / EditUserDialog specs. The mount
// factories stay per-spec (they differ in props, store state, and mocks);
// only the stubs live here.
//
// The dialogs use `<script setup>`, so child components resolve from local
// imports. VTU's `stubs` don't reach them, so each spec swaps them with
// `vi.mock`; the store comes from a testing pinia passed to `mount`. Stubs are
// render functions, not `template`: the runtime-only test build has no compiler.

import { h } from 'vue'

/** Stub that records the props NcDialog receives and renders both slots. */
export const NcDialogStub = {
	name: 'NcDialog',
	props: ['name', 'size', 'noClose', 'closeOnClickOutside', 'outTransition'],
	render() {
		return h('div', [
			this.$slots.default?.(),
			h('div', { 'data-test': 'actions' }, [this.$slots.actions?.()]),
		])
	},
}

/** Stub that forwards attrs (e.g. aria-disabled) and renders icon + default slots. */
export const NcButtonStub = {
	name: 'NcButton',
	inheritAttrs: false,
	render() {
		return h('button', this.$attrs, [
			this.$slots.icon?.(),
			this.$slots.default?.(),
		])
	},
}

/** Stub exposing focusField, which the dialogs call on mount and on errors. */
export const UserFormFieldsStub = {
	name: 'UserFormFields',
	methods: { focusField() {} },
	render: () => h('div'),
}
