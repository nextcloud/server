/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator } from '@playwright/test'

import { expect as baseExpect } from '@playwright/test'

export const expect = baseExpect.extend({
	/**
	 * Asserts that a file-list row has the active highlight class.
	 * A row becomes active when it was the last folder navigated into
	 * (e.g. after a browser back/forward traversal).
	 */
	async toBeActiveRow(received: Locator, options?: { timeout?: number }) {
		let pass: boolean
		let failMessage: string | undefined
		try {
			await baseExpect(received).toHaveClass(/files-list__row--active/, options)
			pass = true
		} catch (e: unknown) {
			pass = false
			failMessage = (e as Error).message
		}
		return {
			message: () => pass
				? 'Expected row not to have class \'files-list__row--active\''
				: failMessage ?? 'Expected row to have class \'files-list__row--active\'',
			pass,
		}
	},
	/**
	 * Asserts that an input element has a specific HTML5 validation message.
	 * An empty string means the input is valid (no validation error).
	 * Retries until the message matches or the timeout expires.
	 */
	async toHaveValidationMessage(received: Locator, expected: string | RegExp, options?: { timeout?: number }) {
		let pass = false
		let actual = ''
		const getMsg = async () => received.evaluate((el) => (el as HTMLInputElement).validationMessage)
		try {
			if (typeof expected === 'string') {
				await baseExpect.poll(getMsg, { timeout: options?.timeout ?? 5000 }).toBe(expected)
			} else {
				await baseExpect.poll(getMsg, { timeout: options?.timeout ?? 5000 }).toMatch(expected)
			}
			pass = true
		} catch {
			actual = await getMsg().catch(() => '')
		}
		return {
			message: () => pass
				? `Expected validation message not to equal ${JSON.stringify(expected)}`
				: `Expected validation message ${JSON.stringify(expected)}, got ${JSON.stringify(actual)}`,
			pass,
		}
	},
})

declare module '@playwright/test' {
	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	interface Matchers<R, T> {
		toBeActiveRow(options?: { timeout?: number }): R
		toHaveValidationMessage(expected: string | RegExp, options?: { timeout?: number }): R
	}
}
