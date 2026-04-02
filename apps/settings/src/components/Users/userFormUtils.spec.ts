/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it } from 'vitest'
import { diffPayload, languageFilterBy, resolveLanguage, userToFormData, validateQuota } from './userFormUtils.ts'

describe('resolveLanguage', () => {
	const serverLanguages = {
		commonLanguages: [
			{ code: 'en', name: 'English' },
			{ code: 'de', name: 'Deutsch' },
		],
		otherLanguages: [
			{ code: 'ja', name: '日本語' },
		],
	}

	it('returns empty language when user has no language set', () => {
		expect(resolveLanguage({ language: '' }, serverLanguages)).toEqual({ code: '', name: '' })
	})

	it('returns empty language when user language is undefined', () => {
		expect(resolveLanguage({}, serverLanguages)).toEqual({ code: '', name: '' })
	})

	it('resolves a common language', () => {
		expect(resolveLanguage({ language: 'de' }, serverLanguages)).toEqual({ code: 'de', name: 'Deutsch' })
	})

	it('resolves an other language', () => {
		expect(resolveLanguage({ language: 'ja' }, serverLanguages)).toEqual({ code: 'ja', name: '日本語' })
	})

	it('falls back to code as name for unknown languages', () => {
		expect(resolveLanguage({ language: 'xx' }, serverLanguages)).toEqual({ code: 'xx', name: 'xx' })
	})

	it('handles missing serverLanguages gracefully', () => {
		expect(resolveLanguage({ language: 'en' }, null)).toEqual({ code: 'en', name: 'en' })
		expect(resolveLanguage({ language: 'en' }, {})).toEqual({ code: 'en', name: 'en' })
	})
})

describe('userToFormData', () => {
	const allGroups = [
		{ id: 'admin', name: 'Admin' },
		{ id: 'devs', name: 'Developers' },
		{ id: 'design', name: 'Design' },
	]

	const quotaOptions = [
		{ id: 'default', label: 'Default quota' },
		{ id: 'none', label: 'Unlimited' },
		{ id: '1 GB', label: '1 GB' },
	]

	const serverLanguages = {
		commonLanguages: [{ code: 'en', name: 'English' }],
		otherLanguages: [],
	}

	it('maps a full user object to form data', () => {
		const user = {
			id: 'bob',
			displayname: 'Bob Smith',
			email: 'bob@example.com',
			groups: ['admin', 'devs'],
			subadmin: ['devs'],
			quota: { quota: 1073741824 }, // 1 GB
			language: 'en',
			manager: 'alice',
		}

		const result = userToFormData(user, allGroups, quotaOptions, serverLanguages)

		expect(result.username).toBe('bob')
		expect(result.displayName).toBe('Bob Smith')
		expect(result.password).toBe('')
		expect(result.email).toBe('bob@example.com')
		expect(result.groups).toEqual([
			{ id: 'admin', name: 'Admin' },
			{ id: 'devs', name: 'Developers' },
		])
		expect(result.subadminGroups).toEqual([
			{ id: 'devs', name: 'Developers' },
		])
		expect(result.quota).toEqual({ id: '1 GB', label: '1 GB' })
		expect(result.language).toEqual({ code: 'en', name: 'English' })
		expect(result.manager).toBe('alice')
	})

	it('defaults missing fields gracefully', () => {
		const user = {
			id: 'minimal',
			groups: [],
			quota: {},
		}

		const result = userToFormData(user, allGroups, quotaOptions, serverLanguages)

		expect(result.displayName).toBe('')
		expect(result.email).toBe('')
		expect(result.manager).toBe('')
		expect(result.groups).toEqual([])
		expect(result.subadminGroups).toEqual([])
	})

	it('uses default quota when quota is "default"', () => {
		const user = { id: 'u1', groups: [], quota: { quota: 'default' } }
		const result = userToFormData(user, allGroups, quotaOptions, serverLanguages)
		expect(result.quota).toEqual({ id: 'default', label: 'Default quota' })
	})

	it('uses unlimited quota when quota is unset', () => {
		const user = { id: 'u1', groups: [], quota: { quota: 'none' } }
		const result = userToFormData(user, allGroups, quotaOptions, serverLanguages)
		expect(result.quota.id).toBe('none')
	})

	it('filters out groups that do not exist in allGroups', () => {
		const user = { id: 'u1', groups: ['admin', 'nonexistent'], quota: {} }
		const result = userToFormData(user, allGroups, quotaOptions, serverLanguages)
		expect(result.groups).toEqual([{ id: 'admin', name: 'Admin' }])
	})
})

describe('diffPayload', () => {
	function makeFormData(overrides = {}) {
		return {
			username: 'bob',
			displayName: 'Bob',
			password: '',
			email: 'bob@example.com',
			groups: [{ id: 'devs', name: 'Developers' }],
			subadminGroups: [],
			quota: { id: '1 GB', label: '1 GB' },
			language: { code: 'en', name: 'English' },
			manager: 'alice',
			...overrides,
		}
	}

	it('returns empty object when nothing changed', () => {
		const data = makeFormData()
		expect(diffPayload(data, { ...data })).toEqual({})
	})

	it('detects displayName change', () => {
		const initial = makeFormData()
		const current = makeFormData({ displayName: 'Robert' })
		expect(diffPayload(initial, current)).toEqual({ displayName: 'Robert' })
	})

	it('always includes password when non-empty', () => {
		const initial = makeFormData()
		const current = makeFormData({ password: 'secret123' })
		expect(diffPayload(initial, current)).toEqual({ password: 'secret123' })
	})

	it('does not include password when empty', () => {
		const initial = makeFormData()
		const current = makeFormData({ password: '' })
		expect(diffPayload(initial, current)).toEqual({})
	})

	it('detects email change', () => {
		const initial = makeFormData()
		const current = makeFormData({ email: 'new@example.com' })
		expect(diffPayload(initial, current)).toEqual({ email: 'new@example.com' })
	})

	it('detects quota change', () => {
		const initial = makeFormData()
		const current = makeFormData({ quota: { id: '5 GB', label: '5 GB' } })
		expect(diffPayload(initial, current)).toEqual({ quota: '5 GB' })
	})

	it('detects language change', () => {
		const initial = makeFormData()
		const current = makeFormData({ language: { code: 'de', name: 'Deutsch' } })
		expect(diffPayload(initial, current)).toEqual({ language: 'de' })
	})

	it('detects manager change from string to object', () => {
		const initial = makeFormData({ manager: 'alice' })
		const current = makeFormData({ manager: { id: 'charlie', displayname: 'Charlie' } })
		expect(diffPayload(initial, current)).toEqual({ manager: 'charlie' })
	})

	it('detects manager change from object to string', () => {
		const initial = makeFormData({ manager: { id: 'alice', displayname: 'Alice' } })
		const current = makeFormData({ manager: 'bob' })
		expect(diffPayload(initial, current)).toEqual({ manager: 'bob' })
	})

	it('no diff when manager string matches object id', () => {
		const initial = makeFormData({ manager: 'alice' })
		const current = makeFormData({ manager: { id: 'alice', displayname: 'Alice' } })
		expect(diffPayload(initial, current)).toEqual({})
	})

	it('detects manager cleared (object to empty string)', () => {
		const initial = makeFormData({ manager: { id: 'alice', displayname: 'Alice' } })
		const current = makeFormData({ manager: '' })
		expect(diffPayload(initial, current)).toEqual({ manager: '' })
	})

	it('handles manager with null id', () => {
		const initial = makeFormData({ manager: '' })
		const current = makeFormData({ manager: { id: null } })
		expect(diffPayload(initial, current)).toEqual({})
	})

	it('detects groups added', () => {
		const initial = makeFormData({ groups: [{ id: 'devs' }] })
		const current = makeFormData({ groups: [{ id: 'devs' }, { id: 'admin' }] })
		const result = diffPayload(initial, current)
		expect(result.groups).toEqual(['admin', 'devs'])
	})

	it('detects groups removed', () => {
		const initial = makeFormData({ groups: [{ id: 'devs' }, { id: 'admin' }] })
		const current = makeFormData({ groups: [{ id: 'devs' }] })
		expect(diffPayload(initial, current)).toEqual({ groups: ['devs'] })
	})

	it('ignores group reordering', () => {
		const initial = makeFormData({ groups: [{ id: 'admin' }, { id: 'devs' }] })
		const current = makeFormData({ groups: [{ id: 'devs' }, { id: 'admin' }] })
		expect(diffPayload(initial, current)).toEqual({})
	})

	it('detects subadmin groups change', () => {
		const initial = makeFormData({ subadminGroups: [] })
		const current = makeFormData({ subadminGroups: [{ id: 'devs' }] })
		expect(diffPayload(initial, current)).toEqual({ subadminGroups: ['devs'] })
	})

	it('detects multiple changes at once', () => {
		const initial = makeFormData()
		const current = makeFormData({
			displayName: 'Robert',
			password: 'newpass',
			email: 'new@example.com',
		})
		const result = diffPayload(initial, current)
		expect(result).toEqual({
			displayName: 'Robert',
			password: 'newpass',
			email: 'new@example.com',
		})
	})
})

describe('validateQuota', () => {
	const fallback = { id: 'default', label: 'Default quota' }

	it('parses a valid quota string', () => {
		expect(validateQuota('1 GB', fallback)).toEqual({ id: '1 GB', label: '1 GB' })
	})

	it('normalizes quota formatting', () => {
		const result = validateQuota('1073741824', fallback)
		expect(result).toEqual({ id: '1 GB', label: '1 GB' })
	})

	it('parses small quota values', () => {
		const result = validateQuota('4 MB', fallback)
		expect(result.id).toBe('4 MB')
	})

	it('returns fallback for invalid input', () => {
		expect(validateQuota('not a size', fallback)).toEqual(fallback)
	})

	it('returns fallback for empty string', () => {
		expect(validateQuota('', fallback)).toEqual(fallback)
	})

	it('returns fallback for negative values', () => {
		expect(validateQuota('-5 GB', fallback)).toEqual(fallback)
	})

	it('accepts zero as a valid quota', () => {
		const result = validateQuota('0', fallback)
		expect(result).toEqual({ id: '0 B', label: '0 B' })
	})
})

describe('languageFilterBy', () => {
	it('matches a plain language option by label', () => {
		expect(languageFilterBy({}, 'English', 'eng')).toBe(true)
	})

	it('rejects a non-matching plain option', () => {
		expect(languageFilterBy({}, 'English', 'deu')).toBe(false)
	})

	it('is case-insensitive', () => {
		expect(languageFilterBy({}, 'Deutsch', 'DEUT')).toBe(true)
	})

	it('matches a group header if any nested language matches', () => {
		const group = {
			languages: [
				{ name: 'English' },
				{ name: 'Deutsch' },
			],
		}
		expect(languageFilterBy(group, 'Common languages', 'deut')).toBe(true)
	})

	it('rejects a group header if no nested language matches', () => {
		const group = {
			languages: [
				{ name: 'English' },
			],
		}
		expect(languageFilterBy(group, 'Common languages', 'fran')).toBe(false)
	})

	it('handles empty label gracefully', () => {
		expect(languageFilterBy({}, '', 'test')).toBe(false)
	})

	it('handles null label gracefully', () => {
		expect(languageFilterBy({}, null as unknown as string, 'test')).toBe(false)
	})
})
