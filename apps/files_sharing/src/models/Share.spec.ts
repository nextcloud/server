/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it, vi } from 'vitest'
import { computed, reactive } from 'vue'
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

	it('triggers reactive updates when sendPasswordByTalk is set', () => {
		const share = reactive(new Share({ id: 1 }))
		const enabled = computed(() => share.sendPasswordByTalk)
		expect(enabled.value).toBe(false)

		share.sendPasswordByTalk = true

		expect(enabled.value).toBe(true)
	})
})
