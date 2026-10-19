/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IToken } from '../store/authtoken.ts'

import { createTestingPinia } from '@pinia/testing'
import { fireEvent, getByRole } from '@testing-library/vue'
import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import AuthTokenAccessDialog from './AuthTokenAccessDialog.vue'
import { TokenType, useAuthTokenStore } from '../store/authtoken.ts'

enableAutoUnmount(afterEach)

const NcDialog = {
	props: ['buttons'],
	template: '<div><slot /><button v-for="button in buttons" :key="button.label" :disabled="button.disabled" @click="button.callback?.()">{{ button.label }}</button></div>',
}

function makeToken(scope: IToken['scope']): IToken {
	return {
		id: 1,
		name: 'Phone',
		type: TokenType.PERMANENT_TOKEN,
		lastActivity: 1700000000,
		canDelete: true,
		canRename: true,
		scope,
	}
}

function mountDialog(token?: IToken) {
	return mount(AuthTokenAccessDialog, {
		props: { token, open: true },
		attachTo: document.body,
		global: {
			stubs: { NcDialog },
			plugins: [createTestingPinia({ createSpy: vi.fn })],
		},
	})
}

describe('AuthTokenAccessDialog', () => {
	it('keeps Save disabled until the access changes', async () => {
		const wrapper = mountDialog(makeToken({ filesystem: false }))
		const save = getByRole(wrapper.element as HTMLElement, 'button', { name: 'Save' })
		expect(save).toBeDisabled()

		await wrapper.find('input[value="files"]').setValue(true)
		expect(save).toBeEnabled()
	})

	it('shows a partly granted area as mixed', () => {
		const wrapper = mountDialog(makeToken({ filesystem: true, scopes: ['dav:calendar:read', 'dav:contacts:read', 'dav:contacts:write'] }))
		const checkbox = (name: string) => getByRole(wrapper.element as HTMLElement, 'checkbox', { name }) as HTMLInputElement

		expect(checkbox('Calendar').indeterminate).toBe(true)
		expect(checkbox('Can edit calendars').checked).toBe(false)
		expect(checkbox('Contacts').checked).toBe(true)
		expect(checkbox('Contacts').indeterminate).toBe(false)
	})

	it('needs at least one area in Custom', async () => {
		const wrapper = mountDialog(makeToken({ filesystem: true, scopes: ['dav:calendar:read', 'dav:calendar:write'] }))
		const save = getByRole(wrapper.element as HTMLElement, 'button', { name: 'Save' })

		await fireEvent.click(getByRole(wrapper.element as HTMLElement, 'checkbox', { name: 'Calendar' }))

		expect(save).toBeDisabled()
	})

	it('saves the new scopes and keeps the ones it can not show', async () => {
		const token = makeToken({ filesystem: false, scopes: ['dav:contacts:read', 'dav:contacts:write', 'app:deck'] })
		const wrapper = mountDialog(token)
		const store = useAuthTokenStore()
		vi.mocked(store.setTokenScopes).mockResolvedValue(true)

		expect(wrapper.text()).toContain('access set outside of Settings')
		await fireEvent.click(getByRole(wrapper.element as HTMLElement, 'checkbox', { name: 'Calendar' }))
		getByRole(wrapper.element as HTMLElement, 'button', { name: 'Save' }).click()
		await flushPromises()

		expect(store.setTokenScopes).toHaveBeenCalledWith(token, ['dav:contacts:read', 'dav:contacts:write', 'dav:calendar:read', 'dav:calendar:write', 'app:deck'])
	})

	it('creates a token with the chosen preset', async () => {
		const wrapper = mountDialog()
		const store = useAuthTokenStore()
		const response = { token: 'secret', loginName: 'alice', deviceToken: makeToken({ filesystem: true }) }
		vi.mocked(store.addToken).mockResolvedValue(response)
		const create = getByRole(wrapper.element as HTMLElement, 'button', { name: 'Create' })
		expect(create).toBeDisabled()

		await wrapper.find('input[type="text"]').setValue('Thunderbird')
		await wrapper.find('input[value="calendarContacts"]').setValue(true)
		create.click()
		await flushPromises()

		expect(store.addToken).toHaveBeenCalledWith('Thunderbird', ['dav:calendar:read', 'dav:calendar:write', 'dav:contacts:read', 'dav:contacts:write'])
		expect(wrapper.emitted('created')).toEqual([[response]])
	})
})
