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
		expect(store.tokens).toContainEqual(deviceToken)
	})

	it('lets a failed request reach the caller so it can be reported', async () => {
		vi.spyOn(axios, 'post').mockRejectedValue(new Error('Request failed'))

		const store = useAuthTokenStore()

		await expect(store.addToken('Laptop')).rejects.toThrow('Request failed')
		expect(store.tokens).toHaveLength(0)
	})
})

// The automock of @nextcloud/axios has no `put`, so those tests assign one
describe('store:authtoken scopes', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		vi.restoreAllMocks()
	})

	it('sends the scopes when creating a token', async () => {
		const post = vi.spyOn(axios, 'post').mockResolvedValue({
			data: { deviceToken, loginName: 'alice', token: 'secret' },
		})

		await useAuthTokenStore().addToken('Laptop', ['dav:calendar:read'])

		expect(post.mock.calls[0][1]).toMatchObject({ name: 'Laptop', scopes: ['dav:calendar:read'] })
	})

	it.each([
		[['dav:calendar:read'], { filesystem: false, scopes: ['dav:calendar:read'] }],
		[['files:files:read', 'files:files:write'], { filesystem: true, scopes: ['files:files:read', 'files:files:write'] }],
		[null, { filesystem: true }],
	])('sets the scopes %o', async (scopes, expected) => {
		const put = axios.put = vi.fn().mockResolvedValue({ data: [] })
		const token = { ...deviceToken, scope: { filesystem: false, scopes: ['dav:contacts:read'] } }

		expect(await useAuthTokenStore().setTokenScopes(token, scopes)).toBe(true)
		expect(put.mock.calls[0][1]).toMatchObject({ scope: { scopes } })
		expect(token.scope).toEqual(expected)
	})

	it('keeps the old scopes when saving fails', async () => {
		axios.put = vi.fn().mockRejectedValue(new Error('Request failed'))
		const scope = { filesystem: false, scopes: ['dav:contacts:read'] }
		const token = { ...deviceToken, scope }

		expect(await useAuthTokenStore().setTokenScopes(token, null)).toBe(false)
		expect(token.scope).toBe(scope)
	})
})
