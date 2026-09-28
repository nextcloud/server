/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IToken } from '../store/authtoken.ts'

import { createTestingPinia } from '@pinia/testing'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import AuthToken from './AuthToken.vue'
import AuthTokenDeleteDialog from './AuthTokenDeleteDialog.vue'
import { TokenType, useAuthTokenStore } from '../store/authtoken.ts'

// AuthToken.vue reads window.oc_defaults at module evaluation time. vi.hoisted
// is hoisted by Vitest above the imports at transform time, so this is set
// on the existing jsdom window before the SFC is first parsed.
vi.hoisted(() => {
	(window as unknown as { oc_defaults: { productName: string } }).oc_defaults = { productName: 'Nextcloud' }
})

// Mock @nextcloud/dialogs so the wipe action's showConfirmation call resolves
// synchronously in tests. Hoisted alongside the rest.
const showConfirmationMock = vi.hoisted(() => vi.fn())
vi.mock('@nextcloud/dialogs', () => ({
	showConfirmation: showConfirmationMock,
}))

function makeToken(overrides: Partial<IToken> = {}): IToken {
	return {
		id: 1,
		name: 'Test device',
		type: TokenType.PERMANENT_TOKEN,
		lastActivity: 1700000000,
		canDelete: true,
		canRename: true,
		scope: { filesystem: true },
		...overrides,
	}
}

function mountAuthToken(token: IToken) {
	return mount(AuthToken, {
		propsData: { token },
		mocks: {
			t: (_: string, text: string) => text,
		},
		stubs: {
			NcActions: true,
			NcActionButton: true,
			NcActionCheckbox: true,
			NcButton: true,
			NcDateTime: true,
			NcIconSvgWrapper: true,
			NcTextField: true,
		},
		pinia: createTestingPinia({
			createSpy: vi.fn,
			initialState: { 'auth-token': { tokens: [token] } },
		}),
	})
}

function mountDeleteDialog(token: IToken, open = true) {
	return mount(AuthTokenDeleteDialog, {
		propsData: { token, open },
		mocks: {
			t: (_: string, text: string) => text,
		},
		stubs: {
			NcDialog: { template: '<div><slot /></div>' },
		},
	})
}

describe('AuthToken revoke flow', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it('does not call deleteToken when the revoke action is triggered (dialog opens first)', async () => {
		const token = makeToken()
		const wrapper = mountAuthToken(token)
		const store = useAuthTokenStore()

		;(wrapper.vm as unknown as { revoke: () => void }).revoke()
		await wrapper.vm.$nextTick()

		const dialog = wrapper.findComponent(AuthTokenDeleteDialog)
		expect(dialog.exists()).toBe(true)
		expect(dialog.props('open')).toBe(true)
		expect(store.deleteToken).not.toHaveBeenCalled()
	})

	it('calls deleteToken only after the dialog emits confirm', async () => {
		const token = makeToken()
		const wrapper = mountAuthToken(token)
		const store = useAuthTokenStore()

		;(wrapper.vm as unknown as { revoke: () => void }).revoke()
		await wrapper.vm.$nextTick()

		const dialog = wrapper.findComponent(AuthTokenDeleteDialog)
		dialog.vm.$emit('confirm')
		dialog.vm.$emit('update:open', false)
		await wrapper.vm.$nextTick()

		expect(store.deleteToken).toHaveBeenCalledTimes(1)
		expect(store.deleteToken).toHaveBeenCalledWith(token)
	})

	it('does not call deleteToken when the dialog is dismissed without confirming', async () => {
		const token = makeToken()
		const wrapper = mountAuthToken(token)
		const store = useAuthTokenStore()

		;(wrapper.vm as unknown as { revoke: () => void }).revoke()
		await wrapper.vm.$nextTick()

		const dialog = wrapper.findComponent(AuthTokenDeleteDialog)
		dialog.vm.$emit('update:open', false)
		await wrapper.vm.$nextTick()

		// Dialog is v-if'd off the tree once closed
		expect(wrapper.findComponent(AuthTokenDeleteDialog).exists()).toBe(false)
		expect(store.deleteToken).not.toHaveBeenCalled()
	})

	it('passes the wipe-pending token to the dialog when revoke is triggered', async () => {
		const token = makeToken({ type: TokenType.WIPING_TOKEN, canRename: false })
		const wrapper = mountAuthToken(token)

		;(wrapper.vm as unknown as { revoke: () => void }).revoke()
		await wrapper.vm.$nextTick()

		const dialog = wrapper.findComponent(AuthTokenDeleteDialog)
		expect(dialog.exists()).toBe(true)
		expect(dialog.props('open')).toBe(true)
		expect((dialog.props('token') as IToken).type).toBe(TokenType.WIPING_TOKEN)
	})
})

describe('AuthToken wipe flow', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it('does not call wipeToken when the user rejects the confirmation', async () => {
		showConfirmationMock.mockResolvedValueOnce(false)
		const token = makeToken()
		const wrapper = mountAuthToken(token)
		const store = useAuthTokenStore()

		await (wrapper.vm as unknown as { wipe: () => Promise<void> }).wipe()

		expect(showConfirmationMock).toHaveBeenCalledTimes(1)
		expect(store.wipeToken).not.toHaveBeenCalled()
	})

	it('calls wipeToken when the user accepts the confirmation', async () => {
		showConfirmationMock.mockResolvedValueOnce(true)
		const token = makeToken()
		const wrapper = mountAuthToken(token)
		const store = useAuthTokenStore()

		await (wrapper.vm as unknown as { wipe: () => Promise<void> }).wipe()

		expect(showConfirmationMock).toHaveBeenCalledTimes(1)
		expect(store.wipeToken).toHaveBeenCalledTimes(1)
		expect(store.wipeToken).toHaveBeenCalledWith(token)
	})
})

describe('AuthTokenDeleteDialog wipe-pending warning', () => {
	it('omits the warning for a normal token', () => {
		const token = makeToken({ type: TokenType.PERMANENT_TOKEN })
		const wrapper = mountDeleteDialog(token)
		expect(wrapper.findComponent(NcNoteCard).exists()).toBe(false)
	})

	it('renders an accessible error NcNoteCard for a wipe-pending token', () => {
		const token = makeToken({ type: TokenType.WIPING_TOKEN })
		const wrapper = mountDeleteDialog(token)

		const noteCard = wrapper.findComponent(NcNoteCard)
		expect(noteCard.exists()).toBe(true)
		expect(noteCard.props('type')).toBe('error')
		expect(noteCard.text()).toMatch(/wipe/i)
	})
})
