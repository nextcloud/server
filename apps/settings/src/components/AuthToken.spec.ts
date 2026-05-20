/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

// AuthToken.vue reads window.oc_defaults at module evaluation time. vi.hoisted
// runs before imports, so this guarantees the property is set on the existing
// jsdom window before the SFC is first parsed.
vi.hoisted(() => {
	(window as unknown as { oc_defaults: { productName: string } }).oc_defaults = { productName: 'Nextcloud' }
})

import type { IToken } from '../store/authtoken.ts'

import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import AuthToken from './AuthToken.vue'
import AuthTokenDeleteDialog from './AuthTokenDeleteDialog.vue'
import AuthTokenWipeDialog from './AuthTokenWipeDialog.vue'
import { TokenType, useAuthTokenStore } from '../store/authtoken.ts'
import { detect } from '../utils/userAgentDetect.ts'

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
		// Vue Test Utils v1 (legacy pipeline) uses propsData; v2 also accepts it
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

		expect(dialog.props('open')).toBe(false)
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

	it('does not call wipeToken when the wipe action is triggered (dialog opens first)', async () => {
		const token = makeToken()
		const wrapper = mountAuthToken(token)
		const store = useAuthTokenStore()

		;(wrapper.vm as unknown as { wipe: () => void }).wipe()
		await wrapper.vm.$nextTick()

		const dialog = wrapper.findComponent(AuthTokenWipeDialog)
		expect(dialog.exists()).toBe(true)
		expect(dialog.props('open')).toBe(true)
		expect(store.wipeToken).not.toHaveBeenCalled()
	})

	it('calls wipeToken only after the dialog emits confirm', async () => {
		const token = makeToken()
		const wrapper = mountAuthToken(token)
		const store = useAuthTokenStore()

		;(wrapper.vm as unknown as { wipe: () => void }).wipe()
		await wrapper.vm.$nextTick()

		const dialog = wrapper.findComponent(AuthTokenWipeDialog)
		dialog.vm.$emit('confirm')
		dialog.vm.$emit('update:open', false)
		await wrapper.vm.$nextTick()

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

describe('Android Chrome detection', () => {
	it('modern Android Chrome (no Build/ string, post-2021) should match androidChrome', () => {
		const ua = 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Mobile Safari/537.36'
		expect(detect(ua)).toEqual({
			id: 'androidChrome',
			version: '132',
		})
	})

	it('legacy Android Chrome (with Build/ string, pre-2021) should match androidChrome', () => {
		const ua = 'Mozilla/5.0 (Linux; Android 10; SM-G973F Build/QP1A) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36'
		expect(detect(ua)).toEqual({
			id: 'androidChrome',
			version: '130',
		})
	})

	it('Android Chrome on tablet (no "Mobile" in UA) should match androidChrome', () => {
		const ua = 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
		expect(detect(ua)).toEqual({
			id: 'androidChrome',
			version: '131',
		})
	})
})

describe('Desktop Chrome regression tests', () => {
	it('Desktop Chrome on Linux should still match chrome', () => {
		const ua = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36'
		expect(detect(ua)).toEqual({
			id: 'chrome',
			version: '132',
			os: 'Linux',
		})
	})
})

describe('Desktop Firefox regression tests', () => {
	it('Desktop Firefox on Linux should still match firefox', () => {
		const ua = 'Mozilla/5.0 (X11; Linux x86_64; rv:124.0) Gecko/20100101 Firefox/124.0'
		expect(detect(ua)).toEqual({
			id: 'firefox',
			version: '124',
			os: 'Linux',
		})
	})
})
