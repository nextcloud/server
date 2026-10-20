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
	template: '<form @submit.prevent><slot /><button v-for="button in buttons" :key="button.label" :type="button.type ?? \'button\'" @click="button.callback?.()">{{ button.label }}</button></form>',
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
	const button = (wrapper: ReturnType<typeof mountDialog>, name: string) => getByRole(wrapper.element as HTMLElement, 'button', { name })
	const checkbox = (wrapper: ReturnType<typeof mountDialog>, name: string) => getByRole(wrapper.element as HTMLElement, 'checkbox', { name }) as HTMLInputElement

	it('saves nothing when the access is unchanged', async () => {
		const dialog = mountDialog(makeToken({ filesystem: false }))
		const store = useAuthTokenStore()

		await fireEvent.click(button(dialog, 'Save'))
		await flushPromises()

		expect(store.setTokenScopes).not.toHaveBeenCalled()
	})

	it('shows a partly granted area as mixed', () => {
		const dialog = mountDialog(makeToken({ filesystem: true, scopes: ['dav:calendar:read', 'dav:contacts:read', 'dav:contacts:write'] }))

		expect(checkbox(dialog, 'Calendar').indeterminate).toBe(true)
		expect(checkbox(dialog, 'Can edit calendars').checked).toBe(false)
		expect(checkbox(dialog, 'Contacts').checked).toBe(true)
		expect(checkbox(dialog, 'Contacts').indeterminate).toBe(false)
	})

	it('needs at least one area in Custom', async () => {
		const dialog = mountDialog(makeToken({ filesystem: true, scopes: ['dav:calendar:read', 'dav:calendar:write'] }))
		const store = useAuthTokenStore()

		await fireEvent.click(checkbox(dialog, 'Calendar'))
		await fireEvent.click(button(dialog, 'Save'))
		await flushPromises()

		expect(checkbox(dialog, 'Files').validity.valid).toBe(false)
		expect(store.setTokenScopes).not.toHaveBeenCalled()
	})

	it('saves the new scopes and keeps the ones it can not show', async () => {
		const token = makeToken({ filesystem: false, scopes: ['dav:contacts:read', 'dav:contacts:write', 'app:deck'] })
		const dialog = mountDialog(token)
		const store = useAuthTokenStore()
		vi.mocked(store.setTokenScopes).mockResolvedValue(true)

		expect(dialog.text()).toContain('access set outside of Settings')
		await fireEvent.click(checkbox(dialog, 'Calendar'))
		await fireEvent.click(button(dialog, 'Save'))
		await flushPromises()

		expect(store.setTokenScopes).toHaveBeenCalledWith(token, ['dav:contacts:read', 'dav:contacts:write', 'dav:calendar:read', 'dav:calendar:write', 'app:deck'])
	})

	it('saves the kept scopes alone when every area is unticked', async () => {
		const token = makeToken({ filesystem: false, scopes: ['dav:calendar:read', 'dav:calendar:write', 'app:deck'] })
		const dialog = mountDialog(token)
		const store = useAuthTokenStore()
		vi.mocked(store.setTokenScopes).mockResolvedValue(true)

		await fireEvent.click(checkbox(dialog, 'Calendar'))
		await fireEvent.click(button(dialog, 'Save'))
		await flushPromises()

		expect(store.setTokenScopes).toHaveBeenCalledWith(token, ['app:deck'])
	})

	it('needs a name to create a token', async () => {
		const dialog = mountDialog()
		const store = useAuthTokenStore()

		await fireEvent.click(button(dialog, 'Create'))
		await flushPromises()

		expect(store.addToken).not.toHaveBeenCalled()
	})

	it('creates a token with the chosen preset', async () => {
		const dialog = mountDialog()
		const store = useAuthTokenStore()
		const response = { token: 'secret', loginName: 'alice', deviceToken: makeToken({ filesystem: true }) }
		vi.mocked(store.addToken).mockResolvedValue(response)

		await dialog.find('input[type="text"]').setValue('Thunderbird')
		await dialog.find('input[value="calendarContacts"]').setValue(true)
		await fireEvent.click(button(dialog, 'Create'))
		await flushPromises()

		expect(store.addToken).toHaveBeenCalledWith('Thunderbird', ['dav:calendar:read', 'dav:calendar:write', 'dav:contacts:read', 'dav:contacts:write'])
		expect(dialog.emitted('created')).toEqual([[response]])
	})
})
