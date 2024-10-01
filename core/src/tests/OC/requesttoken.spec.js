/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, test, vi } from 'vitest'
import { manageToken, setToken } from '../../OC/requesttoken.js'

const eventbus = vi.hoisted(() => ({ emit: vi.fn() }))
vi.mock('@nextcloud/event-bus', () => eventbus)

describe('request token', () => {

	let emit
	let manager
	const token = 'abc123'

	beforeEach(() => {
		emit = vi.fn()
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
		test('fires off an event for @nextcloud/auth', () => {
			setToken('123')

			expect(eventbus.emit).toHaveBeenCalledWith('csrf-token-update', { token: '123' })
		})
	})

})
