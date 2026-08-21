/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IToken } from './authtoken.ts'

import axios from '@nextcloud/axios'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { TokenType, useAuthTokenStore } from './authtoken.ts'

vi.mock('@nextcloud/axios')
vi.mock('@nextcloud/initial-state')

const deviceToken: IToken = {
	id: 7,
	name: 'Laptop',
	type: TokenType.PERMANENT_TOKEN,
	lastActivity: 1700000000,
	canDelete: true,
	canRename: true,
	scope: { filesystem: true },
}

describe('store:authtoken addToken', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		vi.restoreAllMocks()
	})

	it('keeps the created token', async () => {
		vi.spyOn(axios, 'post').mockResolvedValue({
			data: { deviceToken, loginName: 'alice', token: 'secret' },
		})

		const store = useAuthTokenStore()
		const response = await store.addToken('Laptop')

		expect(response.deviceToken).toBe(deviceToken)
		expect(store.tokens).toContain(deviceToken)
	})

	it('lets a failed request reach the caller so it can be reported', async () => {
		vi.spyOn(axios, 'post').mockRejectedValue(new Error('Request failed'))

		const store = useAuthTokenStore()

		await expect(store.addToken('Laptop')).rejects.toThrow('Request failed')
		expect(store.tokens).toHaveLength(0)
	})
})
