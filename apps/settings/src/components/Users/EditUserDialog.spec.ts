/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

// `<script setup>` children and `useStore()` are invisible to VTU stubs/mocks,
// so swap them via `vi.mock` (see dialogTestHelpers).
const { confirmPassword, dispatch } = vi.hoisted(() => ({ confirmPassword: vi.fn(), dispatch: vi.fn() }))

vi.mock('@nextcloud/password-confirmation', () => ({ confirmPassword }))
vi.mock('@nextcloud/dialogs', () => ({ showError: vi.fn(), showSuccess: vi.fn() }))
vi.mock('../../store/index.js', () => ({
	useStore: () => ({
		dispatch,
		getters: {
			getGroups: [],
			getServerData: { languages: [], canChangePassword: true },
			getPasswordPolicyMinLength: 8,
		},
	}),
}))
vi.mock('@nextcloud/vue/components/NcDialog', async () => ({ default: (await import('./dialogTestHelpers.ts')).NcDialogStub }))
vi.mock('@nextcloud/vue/components/NcButton', async () => ({ default: (await import('./dialogTestHelpers.ts')).NcButtonStub }))
vi.mock('./UserFormFields.vue', async () => ({ default: (await import('./dialogTestHelpers.ts')).UserFormFieldsStub }))

// Decouple the dialog test from form-data diffing internals: always report a
// non-empty change set so save() proceeds past its early return. Other exports
// (used transitively by the form sub-components) are kept real.
vi.mock('./userFormUtils.ts', async (importActual) => ({
	...(await importActual()),
	userToFormData: () => ({
		username: 'bob',
		displayName: 'Bob',
		password: '',
		email: '',
		groups: [],
		subadminGroups: [],
		quota: { id: 'default' },
		language: { code: 'en' },
		manager: { id: '' },
	}),
	diffPayload: () => ({ displayName: 'Bobby' }),
}))

import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import EditUserDialog from './EditUserDialog.vue'
import { flushPromises, NcDialogStub } from './dialogTestHelpers.ts'

function mountDialog() {
	return mount(EditUserDialog, {
		propsData: {
			user: { id: 'bob', backendCapabilities: { setPassword: true } },
			quotaOptions: [],
		},
	})
}

describe('EditUserDialog loading feedback', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		confirmPassword.mockResolvedValue(undefined)
	})

	it('does not dispatch a second save request while one is in flight', async () => {
		dispatch.mockReturnValue(new Promise(() => {}))
		const wrapper = mountDialog()

		await wrapper.find('form').trigger('submit')
		await flushPromises()
		await wrapper.find('form').trigger('submit')
		await flushPromises()

		const saveCalls = dispatch.mock.calls.filter(([action]) => action === 'editUserMultiField')
		expect(saveCalls).toHaveLength(1)
	})

	it('marks the form as busy and inert while saving', async () => {
		confirmPassword.mockReturnValue(new Promise(() => {}))
		const wrapper = mountDialog()

		await wrapper.find('form').trigger('submit')

		const form = wrapper.find('form')
		expect(form.attributes('aria-busy')).toBe('true')
		expect(form.attributes('inert')).toBeDefined()
	})

	it('shows a spinner and busy label on the submit button while saving', async () => {
		confirmPassword.mockReturnValue(new Promise(() => {}))
		const wrapper = mountDialog()

		await wrapper.find('form').trigger('submit')

		expect(wrapper.findComponent(NcLoadingIcon).exists()).toBe(true)
		expect(wrapper.find('[data-test="submit"]').text()).toContain('Saving')
	})

	it('sets aria-disabled (not disabled) on the submit button while saving', async () => {
		confirmPassword.mockReturnValue(new Promise(() => {}))
		const wrapper = mountDialog()
		const submit = wrapper.find('[data-test="submit"]')

		expect(submit.attributes('aria-disabled')).toBe('false')
		expect(submit.attributes('disabled')).toBeUndefined()

		await wrapper.find('form').trigger('submit')

		expect(submit.attributes('aria-disabled')).toBe('true')
		expect(submit.attributes('disabled')).toBeUndefined()
	})

	it('prevents closing the dialog while saving', async () => {
		confirmPassword.mockReturnValue(new Promise(() => {}))
		const wrapper = mountDialog()
		const dialog = wrapper.findComponent(NcDialogStub)

		expect(dialog.props('noClose')).toBe(false)

		await wrapper.find('form').trigger('submit')

		expect(dialog.props('noClose')).toBe(true)
	})
})
