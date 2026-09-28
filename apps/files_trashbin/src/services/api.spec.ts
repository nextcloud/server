/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import { emptyTrash } from './api.ts'
import * as ncAuth from '@nextcloud/auth'
import * as ncDialogs from '@nextcloud/dialogs'
import * as logger from '../logger.ts'

const axiosMock = vi.hoisted(() => ({
	delete: vi.fn(),
}))
vi.mock('@nextcloud/axios', () => ({ default: axiosMock }))

describe('files_trashbin: API - emptyTrash', () => {
	beforeEach(() => {
		vi.spyOn(ncAuth, 'getCurrentUser').mockImplementationOnce(() => ({
			uid: 'test',
			displayName: 'Test',
			isAdmin: false,
		}))
	})

	it('shows success', async () => {
		const dialogSpy = vi.spyOn(ncDialogs, 'showSuccess')
		expect(await emptyTrash()).toBe(true)
		expect(axiosMock.delete).toBeCalled()
		expect(dialogSpy).toBeCalledWith('All files have been permanently deleted')
	})

	it('shows failure', async () => {
		axiosMock.delete.mockImplementationOnce(() => { throw new Error() })
		const dialogSpy = vi.spyOn(ncDialogs, 'showError')
		const loggerSpy = vi.spyOn(logger.logger, 'error').mockImplementationOnce(() => {})

		expect(await emptyTrash()).toBe(false)
		expect(axiosMock.delete).toBeCalled()
		expect(dialogSpy).toBeCalledWith('Failed to empty deleted files')
		expect(loggerSpy).toBeCalled()
	})
})
