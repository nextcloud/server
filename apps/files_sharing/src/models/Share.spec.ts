/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it, vi } from 'vitest'
import Vue from 'vue'
import Share from './Share.ts'

vi.mock('../services/SharingService.ts', () => ({
	isFileRequest: () => false,
}))

describe('Share', () => {
	it('defaults sendPasswordByTalk to false when the server omits it', () => {
		const share = new Share({ id: 1 })
		expect(share.sendPasswordByTalk).toBe(false)
	})

	it('keeps the server value of sendPasswordByTalk', () => {
		const share = new Share({ id: 1, send_password_by_talk: true })
		expect(share.sendPasswordByTalk).toBe(true)
	})

	it('triggers reactive updates when sendPasswordByTalk is set', async () => {
		const share = new Share({ id: 1 })
		const vm = new Vue({
			data: { state: share.state },
			computed: {
				enabled(): boolean {
					return share.sendPasswordByTalk
				},
			},
		})
		expect(vm.enabled).toBe(false)

		share.sendPasswordByTalk = true
		await vm.$nextTick()

		expect(vm.enabled).toBe(true)
	})
})
