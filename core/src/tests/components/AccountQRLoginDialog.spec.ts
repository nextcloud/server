/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { enableAutoUnmount, mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import AccountQRLoginDialog from '../../components/AccountMenu/AccountQRLoginDialog.vue'

vi.mock('@vueuse/integrations/useQRCode', async () => {
	const { computed, toValue } = await import('vue')
	return { useQRCode: (text: string) => computed(() => `qr:${toValue(text)}`) }
})

window.OC.theme = { ...window.OC.theme, productName: 'Nextcloud' }

enableAutoUnmount(afterEach)

/**
 * @param data - The created app password
 */
function mountDialog(data: InstanceType<typeof AccountQRLoginDialog>['$props']['data']) {
	return mount(AccountQRLoginDialog, {
		props: { data },
		global: {
			stubs: {
				NcDialog: { template: '<div><slot /></div>' },
			},
		},
	})
}

describe('AccountQRLoginDialog', () => {
	it('renders the login credentials as QR code', () => {
		const wrapper = mountDialog({ loginName: 'alice', token: 'app-password' })

		const img = wrapper.find('img')
		expect(img.attributes('src')).toBe('qr:nc://login/user:alice&password:app-password&server:http://nextcloud.local')
		expect(img.attributes('alt')).toBe('')
	})

	it('uses the one-time login scheme for one-time tokens', () => {
		const wrapper = mountDialog({
			loginName: 'alice',
			token: 'one-time-password',
			deviceToken: {
				id: 1,
				name: 'Mobile',
				lastActivity: Math.floor(Date.now() / 1000),
				type: 3,
				scope: { filesystem: true },
				canDelete: true,
				canRename: false,
			},
		})

		expect(wrapper.find('img').attributes('src')).toBe('qr:nc://onetime-login/user:alice&password:one-time-password&server:http://nextcloud.local')
	})
})
