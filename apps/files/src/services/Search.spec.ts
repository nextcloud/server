/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { getContents } from './Search.ts'

const searchNodes = vi.hoisted(() => vi.fn())
vi.mock('./WebDavSearch.ts', () => ({ searchNodes }))

const store = vi.hoisted(() => vi.fn())
vi.mock('../store/search.ts', () => ({ useSearchStore }))

describe('Search service', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
	})

	it('rejects on error', async () => {
		searchNodes.mockImplementationOnce(() => { throw new Error('expected error') })
		expect(getContents).rejects.toThrow('expected error')
	})
})
