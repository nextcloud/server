/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { cleanup, render } from '@testing-library/vue'
import { afterAll, afterEach, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest'
import LoginForm from '../../../components/login/LoginForm.vue'

describe('core: LoginForm', () => {
	afterEach(cleanup)

	beforeEach(() => {
		// Mock the required global state
		window.OC = {
			theme: {
				name: 'J\'s cloud',
			},
			requestToken: 'request-token',
		}
	})

	/**
	 * Ensure that characters like ' are not double HTML escaped.
	 * This was a bug in https://github.com/nextcloud/server/issues/34990
	 */
	it('does not double escape special characters in product name', () => {
		const page = render(LoginForm, {
			props: {
				username: 'test-user',
			},
		})

		const heading = page.getByRole('heading', { level: 2 })
		expect(heading.textContent).toContain('J\'s cloud')
	})

	it('offers email as login name by default', async () => {
		const page = render(LoginForm)

		const input = await page.findByRole('textbox', { name: /Account name or email/ })
		expect(input).toBeInstanceOf(HTMLInputElement)
	})

	it('offers only account name if email is not enabled', async () => {
		const page = render(LoginForm, {
			propsData: {
				emailStates: ['0', '1'],
			},
		})

		await expect(async () => page.findByRole('textbox', { name: /Account name or email/ })).rejects.toThrow()
		await expect(page.findByRole('textbox', { name: /Account name/ })).resolves.not.toThrow()
	})

	it('fills username from props into form', () => {
		const page = render(LoginForm, {
			props: {
				username: 'test-user',
			},
		})

		const input: HTMLInputElement = page.getByRole('textbox', { name: /Account name or email/ })
		expect(input.id).toBe('user')
		expect(input.name).toBe('user')
		expect(input.value).toBe('test-user')
	})

	describe('', () => {
		beforeAll(() => {
			vi.useFakeTimers()
			// mock timeout of 5 seconds
			const state = document.createElement('input')
			state.type = 'hidden'
			state.id = 'initial-state-core-loginTimeout'
			state.value = btoa(JSON.stringify(5))
			document.body.appendChild(state)
		})

		afterAll(() => {
			vi.useRealTimers()
			document.querySelector('#initial-state-core-loginTimeout')?.remove()
		})

		it('clears password after timeout', () => {
			// mount forms
			const page = render(LoginForm)
			const input: HTMLInputElement = page.getByLabelText('Password', { selector: 'input' })
			input.dispatchEvent(new InputEvent('input', { data: 'MyPassword' }))

			vi.advanceTimersByTime(2500)
			// see its still the value
			expect(input.value).toBe('')

			// Wait for timeout
			vi.advanceTimersByTime(2600)
			expect(input.value).toBe('')
		})
	})
})
