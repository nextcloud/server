/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const capabilities = vi.hoisted(() => ({
	getCapabilities: vi.fn(),
}))
vi.mock('@nextcloud/capabilities', () => capabilities)

vi.mock('@nextcloud/auth', () => ({
	getCurrentUser: () => ({ uid: 'user', displayName: 'User' }),
}))

vi.mock('@nextcloud/axios', () => ({ default: { post: vi.fn() } }))

vi.mock('@nextcloud/event-bus', () => ({
	subscribe: vi.fn(),
	unsubscribe: vi.fn(),
}))

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
	default: {
		name: 'NcButton',
		inheritAttrs: false,
		render(h) {
			return h('button', {
				attrs: this.$attrs,
				on: {
					click: (event: MouseEvent) => this.$emit('click', event),
				},
			}, this.$slots.icon)
		},
	},
}))

vi.mock('@nextcloud/vue/components/NcListItem', () => ({
	default: {
		name: 'NcListItem',
		render(h) {
			return h('li', [
				this.$slots.subname,
				this.$slots['extra-actions'],
				this.$slots.indicator,
			])
		},
	},
}))

vi.mock('@nextcloud/vue/components/NcLoadingIcon', () => ({
	default: {
		name: 'NcLoadingIcon',
		render(h) {
			return h('span')
		},
	},
}))

vi.mock('@nextcloud/vue/functions/dialog', () => ({
	spawnDialog: vi.fn(),
}))

vi.mock('../../components/AccountMenu/AccountQRLoginDialog.vue', () => ({
	default: {
		name: 'AccountQRLoginDialog',
		render(h) {
			return h('div')
		},
	},
}))

vi.mock('vue-material-design-icons/QrcodeScan.vue', () => ({
	default: {
		name: 'IconQrcodeScan',
		render(h) {
			return h('span')
		},
	},
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
			propsData: {
				id: 'profile',
				name: 'Profile',
				href: '/settings/user',
				active: false,
			},
		})

		expect(wrapper.get('button').attributes('aria-label')).toBe('Show QR code for mobile app login')
	})
})
