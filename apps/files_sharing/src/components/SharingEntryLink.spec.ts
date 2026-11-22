/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { ShareType } from '@nextcloud/sharing'
import { shallowMount } from '@vue/test-utils'
import { useQRCode } from '@vueuse/integrations/useQRCode'
import { describe, expect, it, vi } from 'vitest'
import SharingEntryLink from './SharingEntryLink.vue'
import Share from '../models/Share.ts'

vi.mock('@vueuse/integrations/useQRCode', async () => {
	const { computed } = await import('vue')
	return {
		useQRCode: vi.fn((text: () => string) => computed(() => `qr:${text()}`)),
	}
})

vi.mock('@nextcloud/auth', async (importOriginal) => ({
	...await importOriginal(),
	getCurrentUser: () => ({ uid: 'alice' }),
}))

vi.mock('../services/ConfigService.ts', () => ({
	default: class {
		defaultExpirationDate = null
	},
}))

vi.mock('@nextcloud/sharing/ui', () => ({
	getSidebarInlineActions: () => [],
}))

window.OCA.Sharing = { ExternalShareActions: { state: { actions: [] } } }

/**
 * Mount a link share entry with the action menu and the dialog rendering their content.
 */
function mountEntry() {
	const share = new Share({
		id: 1,
		share_type: ShareType.Link,
		token: 'share-token',
		permissions: 1,
	})

	return shallowMount(SharingEntryLink, {
		props: { share, fileInfo: { path: '/', name: 'file.txt' } },
		global: {
			mocks: {
				t: (_app: string, text: string) => text,
			},
			stubs: {
				NcActions: { template: '<div><slot /></div>' },
				NcActionButton: { template: '<button @click="$emit(\'click\', $event)"><slot /></button>' },
				NcDialog: { template: '<div class="dialog"><slot /></div>' },
			},
		},
	})
}

describe('SharingEntryLink QR code', () => {
	it('shows the public share link as QR code', async () => {
		const wrapper = mountEntry()
		expect(wrapper.find('.qr-code-dialog__img').exists()).toBe(false)
		// the QR code is only generated once the dialog is opened
		const qrCodeText = vi.mocked(useQRCode).mock.calls[0]![0] as () => string
		expect(qrCodeText()).toBe('')

		const button = wrapper.findAll('button').filter((button) => button.text() === 'Generate QR code').at(0)
		await button.trigger('click')

		const img = wrapper.find('.qr-code-dialog__img')
		expect(img.attributes('src')).toBe('qr:http://nextcloud.local/index.php/s/share-token')
		expect(img.attributes('alt')).toBe('')
	})
})
