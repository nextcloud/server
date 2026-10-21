/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ITokenScope } from '../store/authtoken.ts'

import { getLanguage, t } from '@nextcloud/l10n'

export type Area = 'files' | 'calendar' | 'contacts'
/** Services that mix every app's data */
export const SERVICES = ['search', 'people'] as const
export type Service = typeof SERVICES[number]
export type Grant = Area | Service | 'filesShare' | 'filesTrash' | 'filesVersions' | 'calendarWrite' | 'contactsWrite'
export type Preset = 'all' | 'files' | 'calendarContacts' | 'custom'
type AreaState = 'all' | 'partial' | 'none'

export interface Selection {
	preset: Preset
	/** Ticked grants, only used when the preset is not `all` */
	grants: Grant[]
}

/** Must match the scope names the server checks */
const GRANT_SCOPES: Record<Grant, string[]> = {
	files: ['files:files:read', 'files:files:write', 'files:dav:read', 'files:dav:write'],
	filesShare: ['files_sharing:shares:write'],
	filesTrash: ['files_trashbin:dav:read', 'files_trashbin:dav:write'],
	filesVersions: ['files_versions:dav:read', 'files_versions:dav:write'],
	calendar: ['dav:calendar:read'],
	calendarWrite: ['dav:calendar:write'],
	contacts: ['dav:contacts:read'],
	contactsWrite: ['dav:contacts:write'],
	search: ['core:search:read'],
	people: ['core:people:read'],
}

export const AREA_OPTIONS: Record<Area, Grant[]> = {
	files: ['filesShare', 'filesTrash', 'filesVersions'],
	calendar: ['calendarWrite'],
	contacts: ['contactsWrite'],
}

export const PRESET_GRANTS: Record<'files' | 'calendarContacts', Grant[]> = {
	files: ['files', 'filesShare'],
	calendarContacts: ['calendar', 'calendarWrite', 'contacts', 'contactsWrite'],
}

export const AREAS = Object.keys(AREA_OPTIONS) as Area[]

export const GRANT_LABELS: Record<Grant, string> = {
	files: t('settings', 'Files'),
	filesShare: t('settings', 'Can create shares and public links'),
	filesTrash: t('settings', 'Can restore deleted files'),
	filesVersions: t('settings', 'Can restore older versions'),
	calendar: t('settings', 'Calendar'),
	calendarWrite: t('settings', 'Can edit calendars'),
	contacts: t('settings', 'Contacts'),
	contactsWrite: t('settings', 'Can edit contacts'),
	// TRANSLATORS Noun: the unified search across all apps, an option an app password can be given
	search: t('settings', 'Search'),
	people: t('settings', 'People lookup'),
}
const GRANTS = Object.keys(GRANT_SCOPES) as Grant[]

/**
 * Read a stored scope into what the picker shows.
 * A grant counts as ticked only when all its scopes are there, and a sub-option only with its area.
 * Everything else is kept as is.
 *
 * @param scopes Stored scopes, undefined for all access
 */
export function fromScopes(scopes?: string[]): { selection: Selection, kept: string[] } {
	if (scopes === undefined) {
		return { selection: { preset: 'all', grants: [] }, kept: [] }
	}

	const held = GRANTS.filter((grant) => GRANT_SCOPES[grant].every((scope) => scopes.includes(scope)))
	const grants = held.filter((grant) => held.includes(parentOf(grant)))
	const shown = grants.flatMap((grant) => GRANT_SCOPES[grant])
	const kept = scopes.filter((scope) => !shown.includes(scope))

	const presets = Object.keys(PRESET_GRANTS) as (keyof typeof PRESET_GRANTS)[]
	const preset = kept.length === 0 ? presets.find((name) => sameItems(PRESET_GRANTS[name], grants)) : undefined
	return { selection: { preset: preset ?? 'custom', grants }, kept }
}

/**
 * Turn the picker's selection back into the scopes to send, null for all access
 *
 * @param selection What the picker shows
 * @param kept Scopes the picker can't show, sent back unchanged
 */
export function toScopes(selection: Selection, kept: string[]): string[] | null {
	if (selection.preset === 'all') {
		return null
	}
	return [...new Set([...selection.grants.flatMap((grant) => GRANT_SCOPES[grant]), ...kept])]
}

/**
 * @param grants Ticked grants
 * @param area Area to check
 */
export function areaState(grants: Grant[], area: Area): AreaState {
	if (!grants.includes(area)) {
		return 'none'
	}
	return AREA_OPTIONS[area].every((option) => grants.includes(option)) ? 'all' : 'partial'
}

/**
 * Tick or untick a grant. An area takes its sub-options with it, a sub-option brings its area.
 *
 * @param grants Ticked grants
 * @param grant Grant to change
 * @param on Whether to tick it
 */
export function setGrant(grants: Grant[], grant: Grant, on: boolean): Grant[] {
	const changed = grant in AREA_OPTIONS ? [grant, ...AREA_OPTIONS[grant as Area]] : [grant]
	if (!on) {
		return grants.filter((held) => !changed.includes(held))
	}
	return [...new Set([...grants, parentOf(grant), ...changed])]
}

/**
 * Short description of what an app password can reach, for its row
 *
 * @param scope Stored scope of the app password
 */
export function accessLabel(scope: ITokenScope): string {
	if (scope.scopes === undefined) {
		return scope.filesystem
			? t('settings', 'All access')
			: t('settings', 'All access without files')
	}

	const { selection, kept } = fromScopes(scope.scopes)
	// Files without sharing keeps its plain name
	const readOnly: Partial<Record<Area, string>> = {
		calendar: t('settings', 'Calendar (read only)'),
		contacts: t('settings', 'Contacts (read only)'),
	}
	const parts = AREAS.flatMap((area) => {
		const state = areaState(selection.grants, area)
		if (state === 'none') {
			return []
		}
		return [(state === 'partial' && readOnly[area]) || GRANT_LABELS[area]]
	})
	parts.push(...SERVICES.filter((service) => selection.grants.includes(service)).map((service) => GRANT_LABELS[service]))
	if (kept.length > 0) {
		// TRANSLATORS Last item of a list such as "Files and other access"
		parts.push(parts.length === 0 ? t('settings', 'Other access') : t('settings', 'other access'))
	}
	if (parts.length === 0) {
		return t('settings', 'No access')
	}
	return new Intl.ListFormat(getLanguage(), { type: 'conjunction' }).format(parts)
}

/**
 * @param grant An area, sub-option or service
 */
function parentOf(grant: Grant): Grant {
	return AREAS.find((area) => AREA_OPTIONS[area].includes(grant)) ?? grant
}

/**
 * Same items in any order
 *
 * @param a First list
 * @param b Second list
 */
export function sameItems<T>(a: T[], b: T[]): boolean {
	return a.length === b.length && a.every((item) => b.includes(item))
}
