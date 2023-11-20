/**
 * @copyright 2022 Louis Chmn <louis@chmn.me>
 *
 * @author Louis Chmn <louis@chmn.me>
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

import {
	ATOMIC_PERMISSIONS,
	BUNDLED_PERMISSIONS,
	addPermissions,
	subtractPermissions,
	hasPermissions,
	permissionsSetIsValid,
	togglePermissions,
	canTogglePermissions,
} from '../lib/SharePermissionsToolBox.js'

describe('SharePermissionsToolBox', () => {
	test('Adding permissions', () => {
		expect(addPermissions(ATOMIC_PERMISSIONS.NONE, ATOMIC_PERMISSIONS.NONE)).toBe(ATOMIC_PERMISSIONS.NONE)
		expect(addPermissions(ATOMIC_PERMISSIONS.NONE, ATOMIC_PERMISSIONS.READ)).toBe(ATOMIC_PERMISSIONS.READ)
		expect(addPermissions(ATOMIC_PERMISSIONS.READ, ATOMIC_PERMISSIONS.READ)).toBe(ATOMIC_PERMISSIONS.READ)
		expect(addPermissions(ATOMIC_PERMISSIONS.READ, ATOMIC_PERMISSIONS.UPDATE)).toBe(ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.UPDATE)
		expect(addPermissions(ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.UPDATE, ATOMIC_PERMISSIONS.CREATE | ATOMIC_PERMISSIONS.DELETE | ATOMIC_PERMISSIONS.SHARE)).toBe(BUNDLED_PERMISSIONS.ALL)
		expect(addPermissions(BUNDLED_PERMISSIONS.ALL, ATOMIC_PERMISSIONS.READ)).toBe(BUNDLED_PERMISSIONS.ALL)
		expect(addPermissions(BUNDLED_PERMISSIONS.ALL, ATOMIC_PERMISSIONS.NONE)).toBe(BUNDLED_PERMISSIONS.ALL)
	})

	test('Subtract permissions', () => {
		expect(subtractPermissions(ATOMIC_PERMISSIONS.READ, ATOMIC_PERMISSIONS.NONE)).toBe(ATOMIC_PERMISSIONS.READ)
		expect(subtractPermissions(ATOMIC_PERMISSIONS.READ, ATOMIC_PERMISSIONS.READ)).toBe(ATOMIC_PERMISSIONS.NONE)
		expect(subtractPermissions(ATOMIC_PERMISSIONS.READ, ATOMIC_PERMISSIONS.UPDATE)).toBe(ATOMIC_PERMISSIONS.READ)
		expect(subtractPermissions(ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.UPDATE, ATOMIC_PERMISSIONS.UPDATE)).toBe(ATOMIC_PERMISSIONS.READ)
		expect(subtractPermissions(ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.UPDATE, ATOMIC_PERMISSIONS.CREATE | ATOMIC_PERMISSIONS.DELETE)).toBe(ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.UPDATE)
		expect(subtractPermissions(ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.UPDATE, ATOMIC_PERMISSIONS.UPDATE | ATOMIC_PERMISSIONS.DELETE)).toBe(ATOMIC_PERMISSIONS.READ)
		expect(subtractPermissions(BUNDLED_PERMISSIONS.ALL, ATOMIC_PERMISSIONS.READ)).toBe(ATOMIC_PERMISSIONS.UPDATE | ATOMIC_PERMISSIONS.CREATE | ATOMIC_PERMISSIONS.DELETE | ATOMIC_PERMISSIONS.SHARE)
	})

	test('Has permissions', () => {
		expect(hasPermissions(ATOMIC_PERMISSIONS.NONE, ATOMIC_PERMISSIONS.READ)).toBe(false)
		expect(hasPermissions(ATOMIC_PERMISSIONS.READ, ATOMIC_PERMISSIONS.NONE)).toBe(true)
		expect(hasPermissions(BUNDLED_PERMISSIONS.READ_ONLY, ATOMIC_PERMISSIONS.READ)).toBe(true)
		expect(hasPermissions(BUNDLED_PERMISSIONS.READ_ONLY, ATOMIC_PERMISSIONS.UPDATE)).toBe(false)
		expect(hasPermissions(BUNDLED_PERMISSIONS.READ_ONLY, ATOMIC_PERMISSIONS.DELETE)).toBe(false)
		expect(hasPermissions(BUNDLED_PERMISSIONS.ALL, ATOMIC_PERMISSIONS.DELETE)).toBe(true)
	})

	test('Toggle permissions', () => {
		expect(togglePermissions(BUNDLED_PERMISSIONS.ALL, BUNDLED_PERMISSIONS.UPLOAD_AND_UPDATE)).toBe(ATOMIC_PERMISSIONS.SHARE)
		expect(togglePermissions(BUNDLED_PERMISSIONS.ALL, BUNDLED_PERMISSIONS.FILE_DROP)).toBe(ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.UPDATE | ATOMIC_PERMISSIONS.DELETE | ATOMIC_PERMISSIONS.SHARE)
		expect(togglePermissions(BUNDLED_PERMISSIONS.ALL, ATOMIC_PERMISSIONS.NONE)).toBe(BUNDLED_PERMISSIONS.ALL)
		expect(togglePermissions(ATOMIC_PERMISSIONS.NONE, BUNDLED_PERMISSIONS.ALL)).toBe(BUNDLED_PERMISSIONS.ALL)
		expect(togglePermissions(ATOMIC_PERMISSIONS.READ, BUNDLED_PERMISSIONS.ALL)).toBe(BUNDLED_PERMISSIONS.ALL)
	})

	test('Permissions set is valid', () => {
		expect(permissionsSetIsValid(ATOMIC_PERMISSIONS.NONE)).toBe(false)
		expect(permissionsSetIsValid(ATOMIC_PERMISSIONS.READ)).toBe(true)
		expect(permissionsSetIsValid(ATOMIC_PERMISSIONS.CREATE)).toBe(true)
		expect(permissionsSetIsValid(ATOMIC_PERMISSIONS.UPDATE)).toBe(false)
		expect(permissionsSetIsValid(ATOMIC_PERMISSIONS.DELETE)).toBe(false)
		expect(permissionsSetIsValid(ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.UPDATE)).toBe(true)
		expect(permissionsSetIsValid(ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.DELETE)).toBe(true)
		expect(permissionsSetIsValid(ATOMIC_PERMISSIONS.CREATE | ATOMIC_PERMISSIONS.UPDATE)).toBe(false)
		expect(permissionsSetIsValid(ATOMIC_PERMISSIONS.CREATE | ATOMIC_PERMISSIONS.DELETE)).toBe(false)
		expect(permissionsSetIsValid(ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.CREATE | ATOMIC_PERMISSIONS.UPDATE)).toBe(true)
		expect(permissionsSetIsValid(ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.CREATE | ATOMIC_PERMISSIONS.DELETE)).toBe(true)
	})

	test('Toggle permissions', () => {
		expect(canTogglePermissions(ATOMIC_PERMISSIONS.READ, ATOMIC_PERMISSIONS.READ)).toBe(false)
		expect(canTogglePermissions(ATOMIC_PERMISSIONS.CREATE, ATOMIC_PERMISSIONS.READ)).toBe(true)
		expect(canTogglePermissions(ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.UPDATE, ATOMIC_PERMISSIONS.READ)).toBe(false)
		expect(canTogglePermissions(ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.DELETE, ATOMIC_PERMISSIONS.READ)).toBe(false)
		expect(canTogglePermissions(ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.CREATE | ATOMIC_PERMISSIONS.UPDATE, ATOMIC_PERMISSIONS.READ)).toBe(false)
		expect(canTogglePermissions(ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.CREATE | ATOMIC_PERMISSIONS.DELETE, ATOMIC_PERMISSIONS.READ)).toBe(false)
		expect(canTogglePermissions(ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.CREATE | ATOMIC_PERMISSIONS.UPDATE, ATOMIC_PERMISSIONS.CREATE)).toBe(true)
		expect(canTogglePermissions(ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.CREATE | ATOMIC_PERMISSIONS.DELETE, ATOMIC_PERMISSIONS.CREATE)).toBe(true)
	})
})
