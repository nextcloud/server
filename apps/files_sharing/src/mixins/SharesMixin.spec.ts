/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it, vi } from 'vitest'
import SharesMixin from './SharesMixin.js'

vi.mock('@nextcloud/dialogs')

function buildContext(updateShare: () => Promise<object>) {
	return {
		share: { id: 42, expireDate: '2026-10-15' },
		errors: {},
		saving: false,
		updateQueue: { add: (task: () => Promise<void>) => task() },
		updateShare,
		updateSuccessMessage: () => '',
		$delete: vi.fn(),
	}
}

describe('SharesMixin.queueUpdate', () => {
	it('takes the expiration date returned by the server', async () => {
		const ctx = buildContext(vi.fn().mockResolvedValue({ expiration: '2026-10-15 23:59:59' }))

		await SharesMixin.methods.queueUpdate.call(ctx, 'expireDate')

		expect(ctx.share.expireDate).toBe('2026-10-15 23:59:59')
	})

	it('keeps an expiration date picked while the request was in flight', async () => {
		const ctx = buildContext(vi.fn().mockImplementation(async () => {
			ctx.share.expireDate = '2026-10-20'
			return { expiration: '2026-10-15 23:59:59' }
		}))

		await SharesMixin.methods.queueUpdate.call(ctx, 'expireDate')

		expect(ctx.share.expireDate).toBe('2026-10-20')
	})
})
