/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import Dialogs from '../../OC/dialogs.js'

const dialog = vi.hoisted(() => ({
	spawnDialog: vi.fn(),
}))
vi.mock('@nextcloud/vue/functions/dialog', () => dialog)

describe('OC.dialogs.prompt', () => {
	beforeEach(() => {
		dialog.spawnDialog.mockReset()
	})

	it('passes the prompt to the dialog', async () => {
		dialog.spawnDialog.mockResolvedValue([true, 'value'])

		await Dialogs.prompt('Your name?', 'Name', vi.fn(), true, 'name-input', true)

		expect(dialog.spawnDialog).toHaveBeenCalledOnce()
		expect(dialog.spawnDialog.mock.calls[0]![1]).toEqual({
			text: 'Your name?',
			name: 'Name',
			inputName: 'name-input',
			isPassword: true,
		})
	})

	it('resolves once the dialog is closed and hands its result to the callback', async () => {
		const { promise, resolve } = Promise.withResolvers<[boolean, string]>()
		dialog.spawnDialog.mockReturnValue(promise)
		const callback = vi.fn()

		const prompt = Dialogs.prompt('Your name?', 'Name', callback)
		expect(callback).not.toHaveBeenCalled()

		resolve([true, 'Jane'])
		await prompt

		expect(callback).toHaveBeenCalledExactlyOnceWith(true, 'Jane')
	})

	it('hands a single close argument to the callback as is', async () => {
		dialog.spawnDialog.mockResolvedValue(false)
		const callback = vi.fn()

		await Dialogs.prompt('Your name?', 'Name', callback)

		expect(callback).toHaveBeenCalledExactlyOnceWith(false)
	})
})
