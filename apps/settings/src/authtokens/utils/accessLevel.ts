/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getLanguage, t } from '@nextcloud/l10n'

export type Area = 'files' | 'calendar' | 'contacts'
export type Preset = 'all' | 'files' | 'calendarContacts' | 'custom'

export interface Selection {
	preset: Preset
	/** Ticked areas, only used when the preset is not `all` */
	areas: Area[]
}

/** Stored scope of an app password, as sent by the server */
export interface TokenScope {
	filesystem?: boolean
	scopes?: string[]
}

/** Scopes each area grants. Must match the scope names the server checks */
export const AREA_SCOPES: Record<Area, string[]> = {
	files: ['files:read', 'files:write', 'files:share'],
	calendar: ['calendar:read', 'calendar:write'],
	contacts: ['contacts:read', 'contacts:write'],
}

export const PRESET_AREAS: Record<'files' | 'calendarContacts', Area[]> = {
	files: ['files'],
	calendarContacts: ['calendar', 'contacts'],
}

const AREAS = Object.keys(AREA_SCOPES) as Area[]

/**
 * Read a stored scope into what the picker shows.
 * An area counts as ticked only when all its scopes are there, everything else is kept as is.
 *
 * @param scopes Stored scopes, undefined for all access
 */
export function fromScopes(scopes?: string[]): { selection: Selection, kept: string[] } {
	if (scopes === undefined) {
		return { selection: { preset: 'all', areas: [] }, kept: [] }
	}

	const areas = AREAS.filter((area) => AREA_SCOPES[area].every((scope) => scopes.includes(scope)))
	const shown = areas.flatMap((area) => AREA_SCOPES[area])
	const kept = scopes.filter((scope) => !shown.includes(scope))

	const presets = Object.keys(PRESET_AREAS) as (keyof typeof PRESET_AREAS)[]
	const preset = kept.length === 0 ? presets.find((name) => sameAreas(PRESET_AREAS[name], areas)) : undefined
	return { selection: { preset: preset ?? 'custom', areas }, kept }
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
	return [...new Set([...selection.areas.flatMap((area) => AREA_SCOPES[area]), ...kept])]
}

/**
 * Short description of what an app password can reach, for its row
 *
 * @param scope Stored scope of the app password
 */
export function accessLabel(scope: TokenScope): string {
	if (scope.scopes === undefined) {
		return scope.filesystem === false
			? t('settings', 'All access without files')
			: t('settings', 'All access')
	}

	const { selection, kept } = fromScopes(scope.scopes)
	const names = {
		files: t('settings', 'Files'),
		calendar: t('settings', 'Calendar'),
		contacts: t('settings', 'Contacts'),
	}
	const parts = selection.areas.map((area) => names[area])
	if (kept.length > 0) {
		parts.push(t('settings', 'Other access'))
	}
	if (parts.length === 0) {
		return t('settings', 'No access')
	}
	return new Intl.ListFormat(getLanguage(), { type: 'conjunction' }).format(parts)
}

/**
 * @param a First list of areas
 * @param b Second list of areas
 */
function sameAreas(a: Area[], b: Area[]): boolean {
	return a.length === b.length && a.every((area) => b.includes(area))
}
