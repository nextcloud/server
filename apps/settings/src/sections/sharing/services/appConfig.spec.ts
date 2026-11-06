/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import { deleteCoreConfigValue, setCoreConfigValue } from './appConfig.ts'

type Callbacks = { success: () => void, error: (error: unknown) => void }

const setValue = vi.fn()
const deleteKey = vi.fn()

describe('sharing: app config service', () => {
	beforeEach(() => {
		setValue.mockReset()
		deleteKey.mockReset()
		window.OCP = { ...window.OCP, AppConfig: { setValue, deleteKey } } as unknown as typeof window.OCP
	})

	it('sets a value of core', async () => {
		setValue.mockImplementation((_app, _key, _value, { success }: Callbacks) => success())

		await expect(setCoreConfigValue('shareapi_allow_links', 'no')).resolves.toBeUndefined()
		expect(setValue).toHaveBeenCalledOnce()
		expect(setValue).toHaveBeenCalledWith('core', 'shareapi_allow_links', 'no', expect.any(Object))
	})

	it('rejects if setting a value fails', async () => {
		const error = new Error('Forbidden')
		setValue.mockImplementation((_app, _key, _value, callbacks: Callbacks) => callbacks.error(error))

		await expect(setCoreConfigValue('shareapi_allow_links', 'no')).rejects.toBe(error)
	})

	it('deletes a value of core', async () => {
		deleteKey.mockImplementation((_app, _key, { success }: Callbacks) => success())

		await expect(deleteCoreConfigValue('shareapi_public_link_disclaimertext')).resolves.toBeUndefined()
		expect(deleteKey).toHaveBeenCalledWith('core', 'shareapi_public_link_disclaimertext', expect.any(Object))
	})

	it('rejects if deleting a value fails', async () => {
		const error = new Error('Forbidden')
		deleteKey.mockImplementation((_app, _key, callbacks: Callbacks) => callbacks.error(error))

		await expect(deleteCoreConfigValue('shareapi_public_link_disclaimertext')).rejects.toBe(error)
	})
})
