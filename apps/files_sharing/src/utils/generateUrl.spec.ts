/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it, vi } from 'vitest'
import { generateFileUrl } from './generateUrl.ts'

const getCapabilities = vi.hoisted(() => vi.fn())
vi.mock('@nextcloud/capabilities', () => ({ getCapabilities }))

describe('generateFileUrl', () => {
	it('should work without globalscale', () => {
		getCapabilities.mockReturnValue({ globalscale: null })
		const url = generateFileUrl(12345)
		expect(url).toBe('http://nextcloud.local/index.php/f/12345')
	})

	it('should work with older globalscale', () => {
		getCapabilities.mockReturnValue({ globalscale: { enabled: true } })
		const url = generateFileUrl(12345)
		expect(url).toBe('http://nextcloud.local/index.php/f/12345')
	})

	it('should work with globalscale', () => {
		getCapabilities.mockReturnValue({ globalscale: { enabled: true, token: 'abc123' } })
		const url = generateFileUrl(12345)
		expect(url).toBe('http://nextcloud.local/index.php/gf/abc123/12345')
	})
})
