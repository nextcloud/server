/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it, vi } from 'vitest'
import { isReactive, reactive } from 'vue'
import SharesMixin from './SharesMixin.js'

vi.mock('@nextcloud/dialogs')

vi.mock('../services/ConfigService.ts', () => ({
	default: class {},
}))

function buildContext(updateShare: () => Promise<object>) {
	return {
		share: { id: 42, expireDate: '2026-10-15' },
		errors: {},
		saving: false,
		updateQueue: { add: (task: () => Promise<void>) => task() },
		updateShare,
		updateSuccessMessage: () => '',
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

	it('stores the new password once it is saved', async () => {
		const ctx = {
			...buildContext(vi.fn().mockResolvedValue({ password_expiration_time: null })),
			share: { id: 42, password: undefined, newPassword: 'secret', passwordExpirationTime: undefined },
		}

		await SharesMixin.methods.queueUpdate.call(ctx, 'password')

		expect(ctx.updateShare).toHaveBeenCalledWith(42, { password: 'secret' })
		expect(ctx.share.password).toBe('secret')
		expect(ctx.share.newPassword).toBeUndefined()
	})
})

describe('SharesMixin.onSyncError', () => {
	/**
	 * @param share The share that failed to sync
	 */
	function buildContext(share: Record<string, unknown>) {
		return {
			share,
			errors: {} as Record<string, string>,
			open: false,
			initialToken: 'initial-token',
			$refs: {},
		}
	}

	it('drops the unsaved password', () => {
		const ctx = buildContext({ password: 'old', newPassword: 'new' })

		SharesMixin.methods.onSyncError.call(ctx, 'password', 'Too short')

		expect(ctx.share.newPassword).toBeUndefined()
		expect(ctx.errors).toEqual({ password: 'Too short' })
		expect(ctx.open).toBe(true)
	})

	it('restores the initial token', () => {
		const ctx = buildContext({ token: 'taken' })

		SharesMixin.methods.onSyncError.call(ctx, 'token', 'Token already in use')

		expect(ctx.share.token).toBe('initial-token')
		expect(ctx.errors).toEqual({ token: 'Token already in use' })
	})
})

describe('SharesMixin.data', () => {
	it('keeps the update queue usable inside the reactive component state', async () => {
		const state = reactive(SharesMixin.data.call({ share: null }))

		expect(isReactive(state.updateQueue)).toBe(false)
		await expect(state.updateQueue.add(async () => 'done')).resolves.toBe('done')
	})
})
