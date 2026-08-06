/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

// `<script setup>` children and `useStore()` are invisible to VTU stubs/mocks,
// so swap them via `vi.mock` (see dialogTestHelpers).
const { dispatch } = vi.hoisted(() => ({ dispatch: vi.fn() }))

vi.mock('../../store/index.js', () => ({
	useStore: () => ({
		dispatch,
		getters: {
			getServerData: { newUserGenerateUserID: false, newUserRequireEmail: false },
			getPasswordPolicyMinLength: 8,
		},
	}),
}))
vi.mock('@nextcloud/vue/components/NcDialog', async () => ({ default: (await import('./dialogTestHelpers.ts')).NcDialogStub }))
vi.mock('@nextcloud/vue/components/NcButton', async () => ({ default: (await import('./dialogTestHelpers.ts')).NcButtonStub }))
vi.mock('./UserFormFields.vue', async () => ({ default: (await import('./dialogTestHelpers.ts')).UserFormFieldsStub }))

import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NewUserDialog from './NewUserDialog.vue'
import { flushPromises, NcDialogStub } from './dialogTestHelpers.ts'

function makeNewUser(overrides = {}) {
	return {
		username: 'alice',
		displayName: '',
		password: 'secret-password',
		email: '',
		groups: [],
		subadminGroups: [],
		quota: { id: 'default' },
		language: { code: 'en' },
		manager: { id: '' },
		...overrides,
	}
}

function mountDialog({ loading = { all: false } } = {}) {
	return mount(NewUserDialog, {
		propsData: {
			loading,
			newUser: makeNewUser(),
			quotaOptions: [],
		},
	})
}

describe('NewUserDialog loading feedback', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it('does not dispatch a second create request while one is in flight', async () => {
		dispatch.mockReturnValue(new Promise(() => {}))
		const wrapper = mountDialog()

		await wrapper.find('form').trigger('submit')
		await wrapper.find('form').trigger('submit')

		const addUserCalls = dispatch.mock.calls.filter(([action]) => action === 'addUser')
		expect(addUserCalls).toHaveLength(1)
	})

	it('marks the form as busy and inert while creating', async () => {
		dispatch.mockReturnValue(new Promise(() => {}))
		const wrapper = mountDialog()

		await wrapper.find('form').trigger('submit')

		const form = wrapper.find('form')
		expect(form.attributes('aria-busy')).toBe('true')
		expect(form.attributes('inert')).toBeDefined()
	})

	it('shows a spinner and busy label on the submit button while creating', async () => {
		dispatch.mockReturnValue(new Promise(() => {}))
		const wrapper = mountDialog()

		await wrapper.find('form').trigger('submit')

		expect(wrapper.findComponent(NcLoadingIcon).exists()).toBe(true)
		expect(wrapper.find('[data-test="submit"]').text()).toContain('Adding new account')
	})

	it('sets aria-disabled (not disabled) on the submit button while creating', async () => {
		dispatch.mockReturnValue(new Promise(() => {}))
		const wrapper = mountDialog()
		const submit = wrapper.find('[data-test="submit"]')

		expect(submit.attributes('aria-disabled')).toBe('false')
		expect(submit.attributes('disabled')).toBeUndefined()

		await wrapper.find('form').trigger('submit')

		expect(submit.attributes('aria-disabled')).toBe('true')
		expect(submit.attributes('disabled')).toBeUndefined()
	})

	it('prevents closing the dialog while creating', async () => {
		dispatch.mockReturnValue(new Promise(() => {}))
		const wrapper = mountDialog()
		const dialog = wrapper.findComponent(NcDialogStub)

		expect(dialog.props('noClose')).toBe(false)

		await wrapper.find('form').trigger('submit')

		expect(dialog.props('noClose')).toBe(true)
	})

	it('re-enables the form when the request fails', async () => {
		const error = { response: { data: { ocs: { meta: { statuscode: 0 } } } } }
		dispatch.mockRejectedValue(error)
		const loading = { all: false }
		const wrapper = mountDialog({ loading })

		await wrapper.find('form').trigger('submit')
		await flushPromises()

		expect(loading.all).toBe(false)
		expect(wrapper.find('form').attributes('aria-busy')).toBe('false')
	})
})
