/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it } from 'vitest'
import Share from './Share.ts'

describe('Share', () => {
	it('exposes the complete public link URL returned by the server', () => {
		const share = new Share({
			id: 1,
			share_type: 3,
			url: 'https://public-gateway.example/prefix/s/token',
		})

		expect(share.url).toBe('https://public-gateway.example/prefix/s/token')
	})
})
