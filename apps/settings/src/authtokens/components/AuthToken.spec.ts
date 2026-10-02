/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IToken } from '../store/authtoken.ts'

import { createTestingPinia } from '@pinia/testing'
import { enableAutoUnmount, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { nextTick } from 'vue'

// AuthToken.vue reads window.OC.theme.productName at module evaluation time.
// vi.hoisted runs before imports, so this guarantees the property is set on
// the existing jsdom window before the SFC is first parsed.
vi.hoisted(() => {
	(window as unknown as { OC: { theme: { productName: string } } }).OC.theme = { productName: 'Nextcloud' }
})

// Mock @nextcloud/dialogs so the wipe action's showConfirmation call resolves
// synchronously in tests. Hoisted so it's installed before AuthToken.vue imports.
const showConfirmationMock = vi.hoisted(() => vi.fn())
vi.mock('@nextcloud/dialogs', () => ({
	showConfirmation: showConfirmationMock,
}))

import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import AuthToken from './AuthToken.vue'
import AuthTokenDeleteDialog from './AuthTokenDeleteDialog.vue'
import AuthTokenSetupDialog from './AuthTokenSetupDialog.vue'
import { TokenType, useAuthTokenStore } from '../store/authtoken.ts'
import { detect } from '../utils/userAgentDetect.ts'

enableAutoUnmount(afterEach)

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
		props: { token },
		global: {
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
			plugins: [createTestingPinia({
				createSpy: vi.fn,
				initialState: { 'auth-token': { tokens: [token] } },
			})],
		},
	})
}

function mountDeleteDialog(token: IToken, open = true) {
	return mount(AuthTokenDeleteDialog, {
		props: { token, open },
		global: {
			mocks: {
				t: (_: string, text: string) => text,
			},
			stubs: {
				NcDialog: { template: '<div><slot /></div>' },
			},
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
		await nextTick()

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
		await nextTick()

		const dialog = wrapper.findComponent(AuthTokenDeleteDialog)
		dialog.vm.$emit('confirm')
		dialog.vm.$emit('update:open', false)
		await nextTick()

		expect(store.deleteToken).toHaveBeenCalledTimes(1)
		expect(store.deleteToken).toHaveBeenCalledWith(token)
	})

	it('does not call deleteToken when the dialog is dismissed without confirming', async () => {
		const token = makeToken()
		const wrapper = mountAuthToken(token)
		const store = useAuthTokenStore()

		;(wrapper.vm as unknown as { revoke: () => void }).revoke()
		await nextTick()

		const dialog = wrapper.findComponent(AuthTokenDeleteDialog)
		dialog.vm.$emit('update:open', false)
		await nextTick()

		// Dialog is v-if'd off the tree once closed
		expect(wrapper.findComponent(AuthTokenDeleteDialog).exists()).toBe(false)
		expect(store.deleteToken).not.toHaveBeenCalled()
	})

	it('passes the wipe-pending token to the dialog when revoke is triggered', async () => {
		const token = makeToken({ type: TokenType.WIPING_TOKEN, canRename: false })
		const wrapper = mountAuthToken(token)

		;(wrapper.vm as unknown as { revoke: () => void }).revoke()
		await nextTick()

		const dialog = wrapper.findComponent(AuthTokenDeleteDialog)
		expect(dialog.exists()).toBe(true)
		expect(dialog.props('open')).toBe(true)
		expect((dialog.props('token') as IToken).type).toBe(TokenType.WIPING_TOKEN)
	})
})

describe('AuthToken rename focus', () => {
	function mountRenamable(token: IToken) {
		return mount(AuthToken, {
			props: { token },
			attachTo: document.body,
			global: {
				mocks: {
					t: (_: string, text: string) => text,
				},
				stubs: {
					NcActions: { template: '<div><button>Device settings</button><slot /></div>' },
					NcActionButton: true,
					NcActionCheckbox: true,
					NcButton: true,
					NcDateTime: true,
					NcIconSvgWrapper: true,
					NcTextField: { template: '<input>', methods: { select() {} } },
				},
				plugins: [createTestingPinia({
					createSpy: vi.fn,
					initialState: { 'auth-token': { tokens: [token] } },
				})],
			},
		})
	}

	function actionsButton(wrapper: ReturnType<typeof mountRenamable>) {
		return wrapper.findAll('button').find((button) => button.text() === 'Device settings')!.element
	}

	it('returns focus to the actions button after cancelling with Escape', async () => {
		const wrapper = mountRenamable(makeToken())

		;(wrapper.vm as unknown as { startRename: () => void }).startRename()
		await nextTick()
		await wrapper.find('input').trigger('keyup', { key: 'Escape' })
		await nextTick()

		expect(wrapper.find('form').exists()).toBe(false)
		expect(document.activeElement).toBe(actionsButton(wrapper))
	})

	it('returns focus to the actions button after saving the new name', async () => {
		const token = makeToken()
		const wrapper = mountRenamable(token)
		const store = useAuthTokenStore()

		;(wrapper.vm as unknown as { startRename: () => void }).startRename()
		await nextTick()
		await wrapper.find('form').trigger('submit')
		await nextTick()

		expect(store.renameToken).toHaveBeenCalledWith(token, token.name)
		expect(document.activeElement).toBe(actionsButton(wrapper))
	})

	// Renaming always asks for the password, and that dialog takes focus until it closes.
	it('returns focus to the actions button once the password confirmation closes', async () => {
		const token = makeToken()
		const wrapper = mountRenamable(token)
		const store = useAuthTokenStore()
		const dialogField = document.createElement('input')
		document.body.appendChild(dialogField)
		let closeDialog = () => {}
		vi.mocked(store.renameToken).mockImplementation(async () => {
			await new Promise((resolve) => setTimeout(resolve))
			dialogField.focus()
			await new Promise<void>((resolve) => {
				closeDialog = resolve
			})
			dialogField.remove()
			return true
		})

		;(wrapper.vm as unknown as { startRename: () => void }).startRename()
		await nextTick()
		await wrapper.find('form').trigger('submit')
		await vi.waitFor(() => expect(document.activeElement).toBe(dialogField))
		closeDialog()

		await vi.waitFor(() => expect(document.activeElement).toBe(actionsButton(wrapper)))
	})
})

describe('AuthToken action labels', () => {
	it('labels each action with its own text', () => {
		const token = makeToken()
		const wrapper = mount(AuthToken, {
			props: { token },
			global: {
				mocks: {
					t: (_: string, text: string) => text,
				},
				stubs: {
					NcActions: { template: '<ul><slot /></ul>' },
					NcActionCheckbox: true,
					NcDateTime: true,
					NcIconSvgWrapper: true,
				},
				plugins: [createTestingPinia({
					createSpy: vi.fn,
					initialState: { 'auth-token': { tokens: [token] } },
				})],
			},
		})

		const labels = wrapper.findAll('button').map((button) => button.text())
		expect(labels).toEqual(expect.arrayContaining(['Rename', 'Revoke', 'Wipe device']))
	})
})

describe('AuthTokenSetupDialog QR code', () => {
	// The login name and password are shown as text, so the QR code adds nothing for assistive technology
	it('hides the QR code from assistive technology', async () => {
		const wrapper = mount(AuthTokenSetupDialog, {
			props: { token: { token: 'app-password', loginName: 'admin', deviceToken: makeToken() } },
			global: {
				mocks: {
					t: (_: string, text: string) => text,
				},
				stubs: {
					NcDialog: { template: '<div><slot /></div>' },
					NcIconSvgWrapper: true,
					// jsdom has no canvas 2D context to draw the QR code on
					VueQrcode: { template: '<canvas />' },
				},
			},
		})

		await wrapper.findAll('button').find((button) => button.text() === 'Show QR code for mobile apps')!.trigger('click')

		const qrCode = wrapper.find('canvas')
		expect(qrCode.exists()).toBe(true)
		expect(qrCode.attributes('aria-hidden')).toBe('true')
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
