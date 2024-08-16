/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { subscribe, unsubscribe } from '@nextcloud/event-bus'

import { manageToken, setToken } from '../../OC/requesttoken.js'

describe('request token', () => {

	let emit
	let manager
	const token = 'abc123'

	beforeEach(() => {
		emit = jest.fn()
		const head = window.document.getElementsByTagName('head')[0]
		head.setAttribute('data-requesttoken', token)

		manager = manageToken(window.document, emit)
	})

	test('reads the token from the document', () => {
		expect(manager.getToken()).toBe('abc123')
	})

	test('remembers the updated token', () => {
		manager.setToken('bca321')

		expect(manager.getToken()).toBe('bca321')
	})

	describe('@nextcloud/auth integration', () => {
		let listener

		beforeEach(() => {
			listener = jest.fn()

			subscribe('csrf-token-update', listener)
		})

		afterEach(() => {
			unsubscribe('csrf-token-update', listener)
		})

		test('fires off an event for @nextcloud/auth', () => {
			setToken('123')

			expect(listener).toHaveBeenCalledWith({ token: '123' })
		})
	})

})
