/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author François Freitag <mail@franek.fr>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
