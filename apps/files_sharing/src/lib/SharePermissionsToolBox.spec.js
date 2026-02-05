/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { describe, expect, test } from 'vitest'
import {
	addPermissions,
	ATOMIC_PERMISSIONS,
	canTogglePermissions,
	getBundledPermissions,
	hasPermissions,
	permissionsSetIsValid,
	subtractPermissions,
	togglePermissions,
} from '../lib/SharePermissionsToolBox.js'

const BUNDLED_PERMISSIONS = getBundledPermissions()

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

	test('Get bundled permissions with SHARE included (default)', () => {
		const permissions = getBundledPermissions()
		expect(permissions.READ_ONLY).toBe(BUNDLED_PERMISSIONS.READ_ONLY)
		expect(permissions.FILE_DROP).toBe(BUNDLED_PERMISSIONS.FILE_DROP)
		expect(permissions.UPLOAD_AND_UPDATE).toBe(BUNDLED_PERMISSIONS.UPLOAD_AND_UPDATE)
		expect(permissions.ALL).toBe(BUNDLED_PERMISSIONS.ALL)
		expect(permissions.ALL_FILE).toBe(BUNDLED_PERMISSIONS.ALL_FILE)
		expect(permissions.ALL).toBe(31)
		expect(permissions.ALL_FILE).toBe(19)
		expect(hasPermissions(permissions.ALL, ATOMIC_PERMISSIONS.SHARE)).toBe(true)
		expect(hasPermissions(permissions.ALL_FILE, ATOMIC_PERMISSIONS.SHARE)).toBe(true)
	})

	test('Get bundled permissions without SHARE (excludeShare=true)', () => {
		const permissions = getBundledPermissions(true)
		expect(permissions.READ_ONLY).toBe(BUNDLED_PERMISSIONS.READ_ONLY)
		expect(permissions.FILE_DROP).toBe(BUNDLED_PERMISSIONS.FILE_DROP)
		expect(permissions.UPLOAD_AND_UPDATE).toBe(BUNDLED_PERMISSIONS.UPLOAD_AND_UPDATE)
		expect(permissions.ALL).toBe(BUNDLED_PERMISSIONS.ALL & ~ATOMIC_PERMISSIONS.SHARE)
		expect(permissions.ALL_FILE).toBe(BUNDLED_PERMISSIONS.ALL_FILE & ~ATOMIC_PERMISSIONS.SHARE)
		expect(permissions.ALL).toBe(15)
		expect(permissions.ALL_FILE).toBe(3)
		expect(hasPermissions(permissions.ALL, ATOMIC_PERMISSIONS.SHARE)).toBe(false)
		expect(hasPermissions(permissions.ALL_FILE, ATOMIC_PERMISSIONS.SHARE)).toBe(false)
	})

	test('Operations with bundled permissions including SHARE', () => {
		const permissionsWithShare = getBundledPermissions(false)
		const permissionsWithoutShare = getBundledPermissions(true)

		// Adding permissions to ALL with SHARE should preserve SHARE
		expect(addPermissions(permissionsWithShare.ALL, ATOMIC_PERMISSIONS.READ)).toBe(permissionsWithShare.ALL)

		// Subtracting READ from ALL with SHARE should leave UPDATE | CREATE | DELETE | SHARE
		expect(subtractPermissions(permissionsWithShare.ALL, ATOMIC_PERMISSIONS.READ))
			.toBe(ATOMIC_PERMISSIONS.UPDATE | ATOMIC_PERMISSIONS.CREATE | ATOMIC_PERMISSIONS.DELETE | ATOMIC_PERMISSIONS.SHARE)

		// Toggle UPLOAD_AND_UPDATE from ALL with SHARE should leave only SHARE
		expect(togglePermissions(permissionsWithShare.ALL, BUNDLED_PERMISSIONS.UPLOAD_AND_UPDATE))
			.toBe(ATOMIC_PERMISSIONS.SHARE)

		// Toggle FILE_DROP from ALL with SHARE
		expect(togglePermissions(permissionsWithShare.ALL, BUNDLED_PERMISSIONS.FILE_DROP))
			.toBe(ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.UPDATE | ATOMIC_PERMISSIONS.DELETE | ATOMIC_PERMISSIONS.SHARE)

		// BUNDLED_PERMISSIONS.ALL already includes SHARE
		expect(BUNDLED_PERMISSIONS.ALL).toBe(permissionsWithShare.ALL)

		// Subtracting SHARE from ALL with SHARE should equal ALL without SHARE
		expect(subtractPermissions(permissionsWithShare.ALL, ATOMIC_PERMISSIONS.SHARE)).toBe(permissionsWithoutShare.ALL)
	})

	test('Operations with bundled permissions for files including SHARE', () => {
		const permissionsWithShare = getBundledPermissions(false)
		const permissionsWithoutShare = getBundledPermissions(true)

		// ALL_FILE with SHARE should be READ | UPDATE | SHARE
		expect(permissionsWithShare.ALL_FILE).toBe(ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.UPDATE | ATOMIC_PERMISSIONS.SHARE)

		// Subtracting SHARE from ALL_FILE with SHARE should equal ALL_FILE without SHARE
		expect(subtractPermissions(permissionsWithShare.ALL_FILE, ATOMIC_PERMISSIONS.SHARE)).toBe(permissionsWithoutShare.ALL_FILE)

		// BUNDLED_PERMISSIONS.ALL_FILE already includes SHARE
		expect(BUNDLED_PERMISSIONS.ALL_FILE).toBe(permissionsWithShare.ALL_FILE)
	})
})
