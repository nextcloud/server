/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it, vi } from 'vitest'
import { reactive } from 'vue'
import SharingLinkList from './SharingLinkList.vue'

/**
 * Build a fake component context with the rendered link share entries.
 *
 * @param entries The rendered entry components
 */
function buildContext(entries: { share: object }[] | undefined) {
	return {
		shares: [] as object[],
		$nextTick: (callback: () => void) => callback(),
		$refs: { entries },
		awaitForShare: SharingLinkList.methods.awaitForShare,
	}
}

describe('SharingLinkList.awaitForShare', () => {
	it('resolves with the entry rendering the share', () => {
		const share = { id: 1 }
		// the entries receive the share through the reactive list
		const entry = { share: reactive(share) }
		const ctx = buildContext([{ share: reactive({ id: 2 }) }, entry])
		const resolve = vi.fn()

		SharingLinkList.methods.awaitForShare.call(ctx, share, resolve)

		expect(resolve).toHaveBeenCalledOnce()
		expect(resolve.mock.calls[0][0]).toBe(entry)
	})

	it('does not resolve when no entry renders the share', () => {
		const ctx = buildContext(undefined)
		const resolve = vi.fn()

		SharingLinkList.methods.awaitForShare.call(ctx, { id: 1 }, resolve)

		expect(resolve).not.toHaveBeenCalled()
	})
})

describe('SharingLinkList.addShare', () => {
	it('adds the share and resolves with its entry', () => {
		const share = { id: 1 }
		const entry = { share: reactive(share) }
		const ctx = buildContext([entry])
		const resolve = vi.fn()

		SharingLinkList.methods.addShare.call(ctx, share, resolve)

		expect(ctx.shares).toEqual([share])
		expect(resolve.mock.calls[0][0]).toBe(entry)
	})
})
