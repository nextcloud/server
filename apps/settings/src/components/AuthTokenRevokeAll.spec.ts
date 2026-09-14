/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IToken } from '../store/authtoken.ts'

import { createTestingPinia } from '@pinia/testing'
import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

// AuthToken.vue, pulled in transitively, reads window.OC.theme.productName at module
// evaluation time. vi.hoisted runs before imports, so it is set before the SFC is parsed.
vi.hoisted(() => {
	(window as unknown as { OC: { theme: { productName: string } } }).OC.theme = { productName: 'Nextcloud' }
})

vi.mock('@nextcloud/initial-state', () => ({
	loadState: vi.fn((_app: string, key: string) => (key === 'app_tokens' ? [] : true)),
}))

import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import AuthTokenRevokeAllDialog from './AuthTokenRevokeAllDialog.vue'
import AuthTokenSection from './AuthTokenSection.vue'
import { TokenType, useAuthTokenStore } from '../store/authtoken.ts'

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

// Renders the `buttons` prop as real buttons, so the callbacks are exercised by clicking
// rather than by reaching into the component instance.
const NcDialogStub = {
	props: ['buttons'],
	template: '<div><slot /><button v-for="(button, index) in buttons" :key="index" @click="button.callback()">{{ button.label }}</button></div>',
}

const NcButtonStub = {
	template: '<button @click="$emit(\'click\')"><slot /></button>',
}

function mountSection(tokens: IToken[]) {
	return mount(AuthTokenSection, {
		mocks: {
			t: (_: string, text: string) => text,
		},
		stubs: {
			AuthTokenList: true,
			AuthTokenSetup: true,
			NcSettingsSection: { template: '<div><slot /></div>' },
			NcButton: NcButtonStub,
			NcDialog: NcDialogStub,
		},
		pinia: createTestingPinia({
			createSpy: vi.fn,
			initialState: { 'auth-token': { tokens } },
		}),
	})
}

function mountDialog(props: { count: number, wipePendingCount: number, open?: boolean }) {
	return mount(AuthTokenRevokeAllDialog, {
		propsData: { open: true, ...props },
		mocks: {
			t: (_: string, text: string) => text,
		},
		stubs: {
			NcDialog: NcDialogStub,
		},
	})
}

describe('AuthTokenSection revoke-all button', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it('hides the button when only the current session exists', () => {
		const wrapper = mountSection([makeToken({ id: 1, current: true })])

		expect(wrapper.find('button').exists()).toBe(false)
		expect(wrapper.findComponent(AuthTokenRevokeAllDialog).exists()).toBe(false)
	})

	it('hides the button when the only other token is wipe-pending', () => {
		const wrapper = mountSection([
			makeToken({ id: 1, current: true }),
			makeToken({ id: 2, type: TokenType.WIPING_TOKEN }),
		])

		expect(wrapper.find('button').exists()).toBe(false)
	})

	it('shows the button and opens the dialog without revoking anything yet', async () => {
		const wrapper = mountSection([
			makeToken({ id: 1, current: true }),
			makeToken({ id: 2 }),
		])
		const store = useAuthTokenStore()

		const button = wrapper.find('button')
		expect(button.exists()).toBe(true)

		await button.trigger('click')

		const dialog = wrapper.findComponent(AuthTokenRevokeAllDialog)
		expect(dialog.exists()).toBe(true)
		expect(dialog.props('open')).toBe(true)
		expect(store.deleteAllOtherTokens).not.toHaveBeenCalled()
	})

	// One-time tokens are hidden from the table but still revoked, so they count.
	it('counts every other token except the wipe-pending ones', async () => {
		const wrapper = mountSection([
			makeToken({ id: 1, current: true }),
			makeToken({ id: 2 }),
			makeToken({ id: 3 }),
			makeToken({ id: 4, type: TokenType.WIPING_TOKEN }),
			makeToken({ id: 5, type: TokenType.ONETIME_TOKEN }),
		])

		await wrapper.find('button').trigger('click')

		const dialog = wrapper.findComponent(AuthTokenRevokeAllDialog)
		expect(dialog.props('count')).toBe(3)
		expect(dialog.props('wipePendingCount')).toBe(1)
	})

	it('revokes only after the dialog emits confirm', async () => {
		const wrapper = mountSection([
			makeToken({ id: 1, current: true }),
			makeToken({ id: 2 }),
		])
		const store = useAuthTokenStore()

		await wrapper.find('button').trigger('click')

		const dialog = wrapper.findComponent(AuthTokenRevokeAllDialog)
		dialog.vm.$emit('confirm')
		dialog.vm.$emit('update:open', false)
		await wrapper.vm.$nextTick()

		expect(store.deleteAllOtherTokens).toHaveBeenCalledTimes(1)
	})

	it('does not revoke when the dialog is dismissed', async () => {
		const wrapper = mountSection([
			makeToken({ id: 1, current: true }),
			makeToken({ id: 2 }),
		])
		const store = useAuthTokenStore()

		await wrapper.find('button').trigger('click')

		const dialog = wrapper.findComponent(AuthTokenRevokeAllDialog)
		dialog.vm.$emit('update:open', false)
		await wrapper.vm.$nextTick()

		expect(wrapper.findComponent(AuthTokenRevokeAllDialog).exists()).toBe(false)
		expect(store.deleteAllOtherTokens).not.toHaveBeenCalled()
	})
})

describe('AuthTokenRevokeAllDialog', () => {
	it('omits the wipe note when nothing is pending a wipe', () => {
		const wrapper = mountDialog({ count: 3, wipePendingCount: 0 })
		expect(wrapper.findComponent(NcNoteCard).exists()).toBe(false)
	})

	it('warns that wipe-pending devices keep access', () => {
		const wrapper = mountDialog({ count: 3, wipePendingCount: 2 })

		const noteCard = wrapper.findComponent(NcNoteCard)
		expect(noteCard.exists()).toBe(true)
		expect(noteCard.props('type')).toBe('warning')
		expect(noteCard.text()).toMatch(/wipe/i)
	})

	it('tells the user the current session is kept', () => {
		const wrapper = mountDialog({ count: 3, wipePendingCount: 0 })
		expect(wrapper.text()).toMatch(/stay signed in here/i)
	})

	it('emits confirm and closes when the destructive button is used', async () => {
		const wrapper = mountDialog({ count: 3, wipePendingCount: 0 })

		const buttons = wrapper.findAll('button')
		await buttons.at(buttons.length - 1).trigger('click')

		expect(wrapper.emitted('confirm')).toHaveLength(1)
		expect(wrapper.emitted('update:open')).toEqual([[false]])
	})

	it('closes without confirming when cancelled', async () => {
		const wrapper = mountDialog({ count: 3, wipePendingCount: 0 })

		await wrapper.findAll('button').at(0).trigger('click')

		expect(wrapper.emitted('confirm')).toBeFalsy()
		expect(wrapper.emitted('update:open')).toEqual([[false]])
	})
})
