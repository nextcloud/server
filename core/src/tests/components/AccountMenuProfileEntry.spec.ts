/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent, h, nextTick } from 'vue'

const capabilities = vi.hoisted(() => ({
	getCapabilities: vi.fn(),
}))
vi.mock('@nextcloud/capabilities', () => capabilities)

vi.mock('@nextcloud/auth', () => ({
	getCurrentUser: () => ({ uid: 'user', displayName: 'User' }),
}))

vi.mock('@nextcloud/axios', () => ({ default: { post: vi.fn() } }))

const eventBus = vi.hoisted(() => ({
	subscribe: vi.fn(),
	unsubscribe: vi.fn(),
}))
vi.mock('@nextcloud/event-bus', () => eventBus)

vi.mock('@nextcloud/initial-state', () => ({
	loadState: vi.fn((_app: string, key: string, fallback: unknown) => {
		if (key === 'profileEnabled') {
			return { profileEnabled: false }
		}
		return fallback
	}),
}))

vi.mock('@nextcloud/l10n', () => ({
	getLanguage: () => 'en',
	t: (_app: string, text: string) => text,
}))

vi.mock('@nextcloud/password-confirmation', () => ({
	addPasswordConfirmationInterceptors: vi.fn(),
	PwdConfirmationMode: {
		Strict: 0,
	},
}))

vi.mock('@nextcloud/router', () => ({
	generateUrl: (path: string) => path,
}))

vi.mock('@nextcloud/vue/components/NcButton', () => ({
	default: defineComponent({
		name: 'NcButton',
		inheritAttrs: false,
		emits: ['click'],
		setup(_, { attrs, emit, slots }) {
			return () => h('button', { ...attrs, onClick: (event: MouseEvent) => emit('click', event) }, slots.icon?.())
		},
	}),
}))

vi.mock('@nextcloud/vue/components/NcListItem', () => ({
	default: defineComponent({
		name: 'NcListItem',
		props: ['name', 'href'],
		setup(props, { slots }) {
			return () => h('li', { 'data-name': props.name, 'data-href': props.href }, [
				slots.subname?.(),
				slots['extra-actions']?.(),
			])
		},
	}),
}))

vi.mock('@nextcloud/vue/functions/dialog', () => ({
	spawnDialog: vi.fn(),
}))

vi.mock('../../components/AccountMenu/AccountQRLoginDialog.vue', () => ({
	default: defineComponent({
		name: 'AccountQRLoginDialog',
		setup() {
			return () => h('div')
		},
	}),
}))

describe('core: AccountMenuProfileEntry', () => {
	beforeEach(() => {
		vi.resetModules()
		capabilities.getCapabilities.mockReturnValue({
			core: {
				'can-create-app-token': true,
			},
		})
	})

	it('labels the QR code button for assistive technologies', async () => {
		const AccountMenuProfileEntry = (await import('../../components/AccountMenu/AccountMenuProfileEntry.vue')).default
		const wrapper = mount(AccountMenuProfileEntry, {
			props: {
				id: 'profile',
				name: 'Profile',
				href: '/settings/user',
				active: false,
			},
		})

		expect(wrapper.get('button').attributes('aria-label')).toBe('Show QR code for mobile app login')
	})

	it.each([
		['settings:profile-enabled:updated', true, 'data-href', '/settings/user'],
		['settings:display-name:updated', 'Jane Doe', 'data-name', 'Jane Doe'],
	])('follows the %s event', async (event, payload, attribute, expected) => {
		eventBus.subscribe.mockClear()
		const AccountMenuProfileEntry = (await import('../../components/AccountMenu/AccountMenuProfileEntry.vue')).default
		const wrapper = mount(AccountMenuProfileEntry, {
			props: {
				id: 'profile',
				name: 'Profile',
				href: '/settings/user',
				active: false,
			},
		})
		const [, handler] = eventBus.subscribe.mock.calls.find(([name]) => name === event)!

		handler(payload)
		await nextTick()

		expect(wrapper.get('li').attributes(attribute)).toBe(expected)
	})
})
