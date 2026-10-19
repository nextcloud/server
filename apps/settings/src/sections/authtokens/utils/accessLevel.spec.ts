/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Selection } from './accessLevel.ts'

import { describe, expect, it } from 'vitest'
import { accessLabel, areaState, fromScopes, setGrant, toScopes } from './accessLevel.ts'

const files = ['files:files:read', 'files:files:write', 'files_sharing:shares:write']
const calendarContacts = ['dav:calendar:read', 'dav:calendar:write', 'dav:contacts:read', 'dav:contacts:write']

describe('accessLevel', () => {
	it.each<[string, Selection]>([
		['files', { preset: 'files', grants: ['files', 'filesShare'] }],
		['calendar and contacts', { preset: 'calendarContacts', grants: ['calendar', 'calendarWrite', 'contacts', 'contactsWrite'] }],
		['files without sharing', { preset: 'custom', grants: ['files'] }],
		['read-only calendar', { preset: 'custom', grants: ['calendar'] }],
	])('round-trips %s', (_, selection) => {
		const scopes = toScopes(selection, [])!
		expect(fromScopes(scopes)).toEqual({ selection, kept: [] })
	})

	it('reads a token without scopes as all access', () => {
		expect(fromScopes(undefined).selection.preset).toBe('all')
		expect(toScopes({ preset: 'all', grants: ['files'] }, [])).toBeNull()
	})

	it('matches presets regardless of order', () => {
		expect(fromScopes([...calendarContacts].reverse()).selection.preset).toBe('calendarContacts')
	})

	it('keeps scopes the picker can not show and sends them back', () => {
		const { selection, kept } = fromScopes([...files, 'app:deck', 'dav:calendar:write'])

		// Write without read is possible through the API, the picker never creates it
		expect(selection).toEqual({ preset: 'custom', grants: ['files', 'filesShare'] })
		expect(kept).toEqual(['app:deck', 'dav:calendar:write'])
		expect(toScopes({ preset: 'custom', grants: ['contacts'] }, kept))
			.toEqual(['dav:contacts:read', 'app:deck', 'dav:calendar:write'])
	})

	it.each([
		[['calendar', 'calendarWrite'], 'all'],
		[['calendar'], 'partial'],
		[['files'], 'none'],
	] as const)('shows %o as %s for the calendar', (grants, state) => {
		expect(areaState([...grants], 'calendar')).toBe(state)
	})

	it('ticks an area with its sub-options and unticks them together', () => {
		expect(setGrant([], 'calendar', true)).toEqual(['calendar', 'calendarWrite'])
		expect(setGrant(['calendar', 'calendarWrite', 'files'], 'calendar', false)).toEqual(['files'])
	})

	it('ticks the area with a sub-option, and unticks only the sub-option', () => {
		expect(setGrant([], 'contactsWrite', true)).toEqual(['contacts', 'contactsWrite'])
		expect(setGrant(['contacts', 'contactsWrite'], 'contactsWrite', false)).toEqual(['contacts'])
	})

	it.each([
		[{ filesystem: true }, 'All access'],
		[{ filesystem: false }, 'All access without files'],
		[{ filesystem: true, scopes: files }, 'Files'],
		[{ filesystem: true, scopes: ['files:files:read', 'files:files:write'] }, 'Files'],
		[{ filesystem: false, scopes: calendarContacts }, 'Calendar and Contacts'],
		[{ filesystem: false, scopes: ['dav:calendar:read'] }, 'Calendar (read only)'],
		[{ filesystem: false, scopes: ['app:deck'] }, 'Other access'],
		[{ filesystem: true, scopes: [...files, 'app:deck'] }, 'Files and other access'],
		[{ filesystem: false, scopes: [] }, 'No access'],
	])('labels %o as %s', (scope, label) => {
		expect(accessLabel(scope)).toBe(label)
	})
})
