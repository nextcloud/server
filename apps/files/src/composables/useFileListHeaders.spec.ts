/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { Header } from '@nextcloud/files'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useFileListHeaders } from './useFileListHeaders.ts'

const getFileListHeaders = vi.hoisted(() => vi.fn())

vi.mock('@nextcloud/files', async (originalModule) => {
	return {
		...(await originalModule()),
		getFileListHeaders,
	}
})

describe('useFileListHeaders', () => {
	beforeEach(() => vi.resetAllMocks())

	it('gets the headers', () => {
		const header = new Header({ id: '1', order: 5, render: vi.fn(), updated: vi.fn() })
		getFileListHeaders.mockImplementationOnce(() => [header])

		const headers = useFileListHeaders()
		expect(headers.value).toEqual([header])
		expect(getFileListHeaders).toHaveBeenCalledOnce()
	})

	it('headers are sorted', () => {
		const header = new Header({ id: '1', order: 10, render: vi.fn(), updated: vi.fn() })
		const header2 = new Header({ id: '2', order: 5, render: vi.fn(), updated: vi.fn() })
		getFileListHeaders.mockImplementationOnce(() => [header, header2])

		const headers = useFileListHeaders()
		// lower order first
		expect(headers.value.map(({ id }) => id)).toStrictEqual(['2', '1'])
		expect(getFileListHeaders).toHaveBeenCalledOnce()
	})
})
