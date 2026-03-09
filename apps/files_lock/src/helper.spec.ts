/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { File, Permission } from '@nextcloud/files'
import { beforeEach, describe, expect, test, vi } from 'vitest'
import { canLock, canUnlock } from './helper'
import { LockType } from './types'

vi.mock('@nextcloud/auth', () => ({
	getCurrentUser: vi.fn(() => ({ uid: 'alice', displayName: 'Alice' })),
}))

const lockedByBob = {
	lock: '1',
	'lock-owner': 'bob',
	'lock-owner-displayname': 'Bob',
	'lock-owner-type': LockType.User,
	'lock-owner-editor': '',
	'lock-time': Date.now(),
	'share-permissions': Permission.ALL,
}

const lockedByAlice = {
	lock: '1',
	'lock-owner': 'alice',
	'lock-owner-displayname': 'Alice',
	'lock-owner-type': LockType.User,
	'lock-owner-editor': '',
	'lock-time': Date.now(),
	'share-permissions': Permission.ALL,
}

const tokenLockedByBob = {
	lock: '1',
	'lock-owner': 'bob',
	'lock-owner-displayname': 'Bob',
	'lock-owner-type': LockType.Token,
	'lock-owner-editor': '',
	'lock-time': Date.now(),
	'share-permissions': Permission.ALL,
}

const unlocked = {
	lock: '',
	'lock-owner': '',
	'lock-owner-displayname': '',
	'lock-owner-type': LockType.User,
	'lock-owner-editor': '',
	'lock-time': 0,
	'share-permissions': Permission.ALL,
}

const makeFile = (owner: string, attributes: Record<string, unknown>, permissions = Permission.ALL) => {
	return new File({
		id: 1,
		source: 'https://cloud.domain.com/remote.php/dav/files/admin/test.txt',
		owner,
		mime: 'text/plain',
		root: '/files/admin',
		permissions,
		attributes,
	})
}

describe('canUnlock', () => {
	test('lock owner can unlock their own lock', () => {
		const file = makeFile('bob', lockedByAlice)
		expect(canUnlock(file)).toBe(true)
	})

	test('file owner can unlock a lock created by another user', () => {
		const file = makeFile('alice', lockedByBob)
		expect(canUnlock(file)).toBe(true)
	})

	test('file owner can unlock a token lock created by another user', () => {
		const file = makeFile('alice', tokenLockedByBob)
		expect(canUnlock(file)).toBe(true)
	})

	test('non-owner non-lock-creator cannot unlock', () => {
		// Current user is alice, file owned by bob, locked by bob
		const file = makeFile('bob', lockedByBob)
		expect(canUnlock(file)).toBe(false)
	})

	test('cannot unlock if file is not locked', () => {
		const file = makeFile('alice', unlocked)
		expect(canUnlock(file)).toBe(false)
	})
})

describe('canLock', () => {
	test('can lock an unlocked updatable file', () => {
		const file = makeFile('alice', unlocked)
		expect(canLock(file)).toBe(true)
	})

	test('cannot lock an already locked file', () => {
		const file = makeFile('alice', lockedByBob)
		expect(canLock(file)).toBe(false)
	})

	test('cannot lock a file without update permission', () => {
		const file = makeFile('alice', unlocked, Permission.READ)
		expect(canLock(file)).toBe(false)
	})
})
