/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it, jest } from '@jest/globals'
import { useFileListHeaders } from './useFileListHeaders.ts'
import { Header } from '@nextcloud/files'
import * as ncFiles from '@nextcloud/files'

jest.mock('@nextcloud/files', () => ({
	...jest.requireActual<typeof import('@nextcloud/files')>('@nextcloud/files'),
	getFileListHeaders: jest.fn(),
}))

describe('useFileListHeaders', () => {
	beforeEach(() => {
		jest.resetAllMocks()
	})

	it('gets the headers', () => {
		const header = new Header({ id: '1', order: 5, render: jest.fn(), updated: jest.fn() })
		// @ts-expect-error its mocked
		ncFiles.getFileListHeaders.mockImplementationOnce(() => [header])

		const headers = useFileListHeaders()
		expect(headers.value).toEqual([header])
		expect(ncFiles.getFileListHeaders).toBeCalled()
	})

	it('headers are sorted', () => {
		const header = new Header({ id: '1', order: 10, render: jest.fn(), updated: jest.fn() })
		const header2 = new Header({ id: '2', order: 5, render: jest.fn(), updated: jest.fn() })
		// @ts-expect-error its mocked
		ncFiles.getFileListHeaders.mockImplementationOnce(() => [header, header2])

		const headers = useFileListHeaders()
		// lower order first
		expect(headers.value.map(({ id }) => id)).toStrictEqual(['2', '1'])
		expect(ncFiles.getFileListHeaders).toBeCalled()
	})
})
