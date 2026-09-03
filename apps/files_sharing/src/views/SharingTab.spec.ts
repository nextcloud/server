/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'

// The component reads the theme at module scope, so it must exist before import.
vi.hoisted(() => {
	window.OC = { ...window.OC, theme: { productName: 'Nextcloud' } }
})

import SharingTab from './SharingTab.vue'
import { openShareCreateDialog } from '../services/SharingDialog.ts'
import { getSharesForNode } from '../services/unifiedShares.ts'

vi.mock('../services/SharingDialog.ts', () => ({
	openShareCreateDialog: vi.fn().mockResolvedValue(undefined),
	isSharingDialogAvailable: vi.fn().mockReturnValue(true),
}))

vi.mock('../services/unifiedShares.ts', () => ({
	getSharesForNode: vi.fn().mockResolvedValue([]),
}))

vi.mock('../services/logger.ts', () => ({
	default: { error: vi.fn(), debug: vi.fn() },
}))

function buildContext(overrides: Record<string, unknown> = {}) {
	return {
		fileInfo: { node: { fileid: 1 } },
		unifiedShares: [],
		loading: false,
		error: '',
		getUnifiedShares: vi.fn().mockResolvedValue(undefined),
		...overrides,
	}
}

type Ctx = ReturnType<typeof buildContext>

/**
 * Call a component method with a fake `this`, as the other specs here do.
 *
 * @param method The method name
 * @param ctx The fake component context
 * @param args Arguments passed to the method
 */
function call(method: string, ctx: Ctx, ...args: unknown[]) {
	return SharingTab.methods[method].call(ctx, ...args)
}

beforeEach(() => vi.clearAllMocks())

describe('openShareDialog', () => {
	it('refreshes the list once the dialog closes', async () => {
		const ctx = buildContext()
		await call('openShareDialog', ctx)
		expect(openShareCreateDialog).toHaveBeenCalledWith(ctx.fileInfo.node)
		// Without the loading state, so the rendered rows stay mounted.
		expect(ctx.getUnifiedShares).toHaveBeenCalledWith(false)
	})

	it('still refreshes when the dialog fails', async () => {
		vi.mocked(openShareCreateDialog).mockRejectedValueOnce(new Error('nope'))
		const ctx = buildContext()
		await call('openShareDialog', ctx)
		expect(ctx.getUnifiedShares).toHaveBeenCalledWith(false)
	})
})

describe('getUnifiedShares', () => {
	it('loads the shares of the current node', async () => {
		const shares = [{ id: '1' }]
		vi.mocked(getSharesForNode).mockResolvedValueOnce(shares)
		const ctx = buildContext({ getUnifiedShares: SharingTab.methods.getUnifiedShares })
		await SharingTab.methods.getUnifiedShares.call(ctx)
		expect(getSharesForNode).toHaveBeenCalledWith(ctx.fileInfo.node)
		expect(ctx.unifiedShares).toEqual(shares)
	})

	it('keeps the rows mounted while refreshing in place', async () => {
		const ctx = buildContext()
		let loadingWhileFetching = false
		vi.mocked(getSharesForNode).mockImplementationOnce(async () => {
			loadingWhileFetching = ctx.loading
			return []
		})
		await SharingTab.methods.getUnifiedShares.call(ctx, false)
		expect(loadingWhileFetching).toBe(false)
	})

	it('surfaces the backend error message', async () => {
		vi.mocked(getSharesForNode).mockRejectedValueOnce({
			response: { data: { ocs: { meta: { message: 'Nope' } } } },
		})
		const ctx = buildContext()
		await SharingTab.methods.getUnifiedShares.call(ctx)
		expect(ctx.error).toBe('Nope')
		expect(ctx.loading).toBe(false)
	})
})
