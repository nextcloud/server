/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { File, Folder } from '@nextcloud/files'
import { mount } from '@vue/test-utils'
import { beforeAll, describe, expect, test, vi } from 'vitest'
import FilesHeaderPendingShares from './FilesHeaderPendingShares.vue'
import { getContents } from '../services/SharingService.ts'

vi.mock('../services/SharingService.ts', () => ({ getContents: vi.fn() }))
vi.mock('../services/PendingSharesService.ts', () => ({
	acceptShare: vi.fn(),
	canRejectShare: vi.fn(() => true),
	rejectShare: vi.fn(),
}))

beforeAll(() => {
	window.OC = { ...window.OC, MimeType: { getIconUrl: () => '/icon.svg' } }
})

/**
 * Mount the header with the given pending shares
 *
 * @param contents The pending share nodes
 */
async function mountWithShares(contents: (File | Folder)[]) {
	vi.mocked(getContents).mockResolvedValue({ folder: {} as Folder, contents })
	const wrapper = mount(FilesHeaderPendingShares)
	await (wrapper.vm as unknown as { update: () => Promise<void> }).update()
	await wrapper.vm.$nextTick()
	return wrapper
}

describe('Pending shares header', () => {
	test('Nothing is rendered without pending shares', async () => {
		const wrapper = await mountWithShares([])
		expect(wrapper.find('.pending-shares').exists()).toBe(false)
	})

	test('Display names with special characters are not escaped twice', async () => {
		const wrapper = await mountWithShares([new File({
			id: 1,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Budget.md',
			owner: 'foo',
			mime: 'text/markdown',
			root: '/files/admin',
			attributes: { 'owner-display-name': 'Foo & Bar' },
		})])

		expect(wrapper.find('.pending-shares__details').text()).toBe('Shared by Foo & Bar')
		expect(wrapper.find('.pending-shares__name').text()).toBe('Budget.md')
		expect(wrapper.find('.pending-shares__share button').text()).toBe('Accept file')
	})

	test('The accept button is labeled by its visible text only', async () => {
		const wrapper = await mountWithShares([new Folder({
			id: 2,
			source: 'https://cloud.domain.com/remote.php/dav/files/admin/Team photos',
			owner: 'foo',
			root: '/files/admin',
			attributes: { 'owner-display-name': 'Foo' },
		})])

		const button = wrapper.find('.pending-shares__share button')
		expect(button.text()).toBe('Accept folder')
		expect(button.attributes('aria-label')).toBeUndefined()
	})
})
