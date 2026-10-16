/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Selection } from './accessLevel.ts'

import { describe, expect, it } from 'vitest'
import { accessLabel, fromScopes, toScopes } from './accessLevel.ts'

const files = ['files:files:read', 'files:files:write', 'files_sharing:shares:write']
const calendarContacts = ['dav:calendar:read', 'dav:calendar:write', 'dav:contacts:read', 'dav:contacts:write']

describe('accessLevel', () => {
	it.each<[string, Selection]>([
		['files', { preset: 'files', areas: ['files'] }],
		['calendar and contacts', { preset: 'calendarContacts', areas: ['calendar', 'contacts'] }],
		['custom', { preset: 'custom', areas: ['files', 'calendar'] }],
	])('round-trips %s', (_, selection) => {
		const scopes = toScopes(selection, [])!
		expect(fromScopes(scopes)).toEqual({ selection, kept: [] })
	})

	it('reads a token without scopes as all access', () => {
		expect(fromScopes(undefined).selection.preset).toBe('all')
		expect(toScopes({ preset: 'all', areas: ['files'] }, [])).toBeNull()
	})

	it('matches presets regardless of order', () => {
		expect(fromScopes([...calendarContacts].reverse()).selection.preset).toBe('calendarContacts')
	})

	it('keeps scopes the picker can not show and sends them back', () => {
		const { selection, kept } = fromScopes([...files, 'app:deck', 'dav:calendar:read'])

		expect(selection).toEqual({ preset: 'custom', areas: ['files'] })
		expect(kept).toEqual(['app:deck', 'dav:calendar:read'])
		expect(toScopes({ preset: 'custom', areas: ['contacts'] }, kept))
			.toEqual(['dav:contacts:read', 'dav:contacts:write', 'app:deck', 'dav:calendar:read'])
	})

	it.each([
		[{ filesystem: true }, 'All access'],
		[{ filesystem: false }, 'All access without files'],
		[{ filesystem: true, scopes: files }, 'Files'],
		[{ filesystem: false, scopes: calendarContacts }, 'Calendar and Contacts'],
		[{ filesystem: false, scopes: ['app:deck'] }, 'Other access'],
		[{ filesystem: false, scopes: [] }, 'No access'],
	])('labels %o as %s', (scope, label) => {
		expect(accessLabel(scope)).toBe(label)
	})
})
