/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NewUserDialog from './NewUserDialog.vue'
import { flushPromises, NcButtonStub, NcDialogStub, UserFormFieldsStub } from './dialogTestHelpers.ts'

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

function mountDialog({ dispatch = vi.fn(), loading = { all: false } } = {}) {
	return mount(NewUserDialog, {
		propsData: {
			loading,
			newUser: makeNewUser(),
			quotaOptions: [],
		},
		mocks: {
			t: (_app: string, text: string) => text,
			$store: {
				dispatch,
				getters: {
					getServerData: { newUserGenerateUserID: false, newUserRequireEmail: false },
					getPasswordPolicyMinLength: 8,
				},
			},
		},
		stubs: {
			NcDialog: NcDialogStub,
			NcButton: NcButtonStub,
			UserFormFields: UserFormFieldsStub,
		},
	})
}

describe('NewUserDialog loading feedback', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it('does not dispatch a second create request while one is in flight', async () => {
		const dispatch = vi.fn().mockReturnValue(new Promise(() => {}))
		const wrapper = mountDialog({ dispatch })

		await wrapper.find('form').trigger('submit')
		await wrapper.find('form').trigger('submit')

		const addUserCalls = dispatch.mock.calls.filter(([action]) => action === 'addUser')
		expect(addUserCalls).toHaveLength(1)
	})

	it('marks the form as busy and inert while creating', async () => {
		const dispatch = vi.fn().mockReturnValue(new Promise(() => {}))
		const wrapper = mountDialog({ dispatch })

		await wrapper.find('form').trigger('submit')

		const form = wrapper.find('form')
		expect(form.attributes('aria-busy')).toBe('true')
		expect(form.attributes('inert')).toBeDefined()
	})

	it('shows a spinner and busy label on the submit button while creating', async () => {
		const dispatch = vi.fn().mockReturnValue(new Promise(() => {}))
		const wrapper = mountDialog({ dispatch })

		await wrapper.find('form').trigger('submit')

		expect(wrapper.findComponent(NcLoadingIcon).exists()).toBe(true)
		expect(wrapper.find('[data-test="submit"]').text()).toContain('Adding new account')
	})

	it('sets aria-disabled (not disabled) on the submit button while creating', async () => {
		const dispatch = vi.fn().mockReturnValue(new Promise(() => {}))
		const wrapper = mountDialog({ dispatch })
		const submit = wrapper.find('[data-test="submit"]')

		expect(submit.attributes('aria-disabled')).toBe('false')
		expect(submit.attributes('disabled')).toBeUndefined()

		await wrapper.find('form').trigger('submit')

		expect(submit.attributes('aria-disabled')).toBe('true')
		expect(submit.attributes('disabled')).toBeUndefined()
	})

	it('prevents closing the dialog while creating', async () => {
		const dispatch = vi.fn().mockReturnValue(new Promise(() => {}))
		const wrapper = mountDialog({ dispatch })
		const dialog = wrapper.findComponent(NcDialogStub)

		expect(dialog.props('noClose')).toBe(false)

		await wrapper.find('form').trigger('submit')

		expect(dialog.props('noClose')).toBe(true)
	})

	it('re-enables the form when the request fails', async () => {
		const error = { response: { data: { ocs: { meta: { statuscode: 0 } } } } }
		const dispatch = vi.fn().mockRejectedValue(error)
		const loading = { all: false }
		const wrapper = mountDialog({ dispatch, loading })

		await wrapper.find('form').trigger('submit')
		await flushPromises()

		expect(loading.all).toBe(false)
		expect(wrapper.find('form').attributes('aria-busy')).toBe('false')
	})
})
