/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import NewFileRequestDialogFinish from './NewFileRequestDialog/NewFileRequestDialogFinish.vue'
import SharingEntryLink from './SharingEntryLink.vue'

const generateUrl = vi.hoisted(() => vi.fn())
const getBaseUrl = vi.hoisted(() => vi.fn())
vi.mock('@nextcloud/router', async (importOriginal) => ({
	...await importOriginal<typeof import('@nextcloud/router')>(),
	generateUrl,
	getBaseUrl,
}))

const components = [
	['SharingEntryLink', SharingEntryLink],
	['NewFileRequestDialogFinish', NewFileRequestDialogFinish],
] as const

beforeEach(() => {
	vi.clearAllMocks()
})

describe.each(components)('%s public share link', (_name, component) => {
	it('uses the complete URL returned by the server', () => {
		generateUrl.mockReturnValue('http://nextcloud.local/s/token')

		const shareLink = component.computed.shareLink.call({
			share: {
				token: 'token',
				url: 'https://public-gateway.example/s/token',
			},
		})

		expect(shareLink).toBe('https://public-gateway.example/s/token')
		expect(generateUrl).not.toHaveBeenCalled()
	})

	it('falls back to the existing URL generation when the server URL is absent', () => {
		getBaseUrl.mockReturnValue('/nextcloud')
		generateUrl.mockReturnValue('http://nextcloud.local/nextcloud/s/token')

		const shareLink = component.computed.shareLink.call({
			share: { token: 'token' },
		})

		expect(shareLink).toBe('http://nextcloud.local/nextcloud/s/token')
		expect(generateUrl).toHaveBeenCalledWith('/s/{token}', { token: 'token' }, { baseURL: '/nextcloud' })
	})
})
