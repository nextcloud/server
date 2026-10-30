/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import { beforeEach, expect, test, vi } from 'vitest'
import { getSharedResources } from './sharedResources.ts'

vi.mock('@nextcloud/axios', () => ({
	default: {
		get: vi.fn(),
	},
}))

vi.mock('@nextcloud/router', () => ({
	generateOcsUrl: vi.fn(() => '/ocs/v2.php/apps/profile/api/v1/resources/alice'),
}))

beforeEach(() => {
	vi.clearAllMocks()
})

test('getSharedResources fetches and returns shared resources', async () => {
	const resources = [
		{
			label: 'Shared folder',
			text: '2 days ago',
			href: 'https://example.com/f/1',
			img: 'https://example.com/preview/1',
		},
	]

	vi.mocked(axios.get).mockResolvedValue({
		data: {
			ocs: {
				data: resources,
			},
		},
	})

	await expect(getSharedResources('alice')).resolves.toEqual(resources)
	expect(generateOcsUrl).toHaveBeenCalledWith('/apps/profile/api/v1/resources/{userId}', { userId: 'alice' })
	expect(axios.get).toHaveBeenCalledWith('/ocs/v2.php/apps/profile/api/v1/resources/alice')
})
