/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ITokenScope } from '../store/authtoken.ts'

import { loadState } from '@nextcloud/initial-state'
import { getLanguage, t } from '@nextcloud/l10n'

export type Area = 'files' | 'calendar' | 'contacts'
/** Services that mix every app's data */
export const SERVICES = ['search', 'people'] as const
export type Service = typeof SERVICES[number]
export type Grant = Area | Service | 'filesEdit' | 'filesShare' | 'filesTrash' | 'filesVersions' | 'calendarWrite' | 'contactsWrite'
export type Preset = 'all' | 'files' | 'readOnly' | 'calendarContacts' | 'custom'
type AreaState = 'all' | 'partial' | 'none'

export interface IGrantableApp {
	id: string
	name: string
	icon: string
	/** Whether the app shows other apps' data */
	acrossApps: boolean
}

export const GRANTABLE_APPS = loadState<IGrantableApp[]>('settings', 'grantable_apps', [])

export interface Selection {
	preset: Preset
	/** Ticked apps, stored as their app id */
	apps: string[]
	/** Ticked grants, only used when the preset is not `all` */
	grants: Grant[]
}

/** Must match the scope names the server checks */
const GRANT_SCOPES: Record<Grant, string[]> = {
	files: ['files:files:read', 'files:dav:read'],
	filesEdit: ['files:files:write', 'files:dav:write'],
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
	files: ['filesEdit', 'filesShare', 'filesTrash', 'filesVersions'],
	calendar: ['calendarWrite'],
	contacts: ['contactsWrite'],
}

export const PRESET_GRANTS: Record<'files' | 'readOnly' | 'calendarContacts', Grant[]> = {
	files: ['files', 'filesEdit', 'filesShare'],
	readOnly: ['files'],
	calendarContacts: ['calendar', 'calendarWrite', 'contacts', 'contactsWrite'],
}

export const AREAS = Object.keys(AREA_OPTIONS) as Area[]

/** Restoring writes into the files, so it only works with editing */
const NEEDS: Partial<Record<Grant, Grant>> = {
	filesTrash: 'filesEdit',
	filesVersions: 'filesEdit',
}

export const GRANT_LABELS: Record<Grant, string> = {
	files: t('settings', 'Files'),
	filesEdit: t('settings', 'Can edit files'),
	filesShare: t('settings', 'Can create shares and public links'),
	filesTrash: t('settings', 'Can restore deleted files'),
	filesVersions: t('settings', 'Can restore older versions'),
	calendar: t('settings', 'Calendar'),
	calendarWrite: t('settings', 'Can edit calendars'),
	contacts: t('settings', 'Contacts'),
	contactsWrite: t('settings', 'Can edit contacts'),
	search: t('settings', 'Search across all apps'),
	people: t('settings', 'People lookup across all apps'),
}
/** An area is read only without its write grant */
const READ_ONLY: Record<Area, { write: Grant, label: string }> = {
	files: { write: 'filesEdit', label: t('settings', 'Files (read only)') },
	calendar: { write: 'calendarWrite', label: t('settings', 'Calendar (read only)') },
	contacts: { write: 'contactsWrite', label: t('settings', 'Contacts (read only)') },
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
		return { selection: { preset: 'all', grants: [], apps: [] }, kept: [] }
	}

	const held = GRANTS.filter((grant) => GRANT_SCOPES[grant].every((scope) => scopes.includes(scope)))
	const grants = held.filter((grant) => held.includes(parentOf(grant)))
	const apps = GRANTABLE_APPS.map((app) => app.id).filter((id) => scopes.includes(id))
	const shown = [...grants.flatMap((grant) => GRANT_SCOPES[grant]), ...apps]
	const kept = scopes.filter((scope) => !shown.includes(scope))

	const presets = Object.keys(PRESET_GRANTS) as (keyof typeof PRESET_GRANTS)[]
	const preset = kept.length === 0 && apps.length === 0 ? presets.find((name) => sameItems(PRESET_GRANTS[name], grants)) : undefined
	return { selection: { preset: preset ?? 'custom', grants, apps }, kept }
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
	return [...new Set([...selection.grants.flatMap((grant) => GRANT_SCOPES[grant]), ...selection.apps, ...kept])]
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
 * Tick or untick a grant. An area takes its sub-options with it, a sub-option brings its area
 * and what it needs, and unticking a sub-option also unticks what needs it.
 *
 * @param grants Ticked grants
 * @param grant Grant to change
 * @param on Whether to tick it
 */
export function setGrant(grants: Grant[], grant: Grant, on: boolean): Grant[] {
	const changed = grant in AREA_OPTIONS ? [grant, ...AREA_OPTIONS[grant as Area]] : [grant]
	if (!on) {
		return grants.filter((held) => !changed.includes(held) && !changed.includes(NEEDS[held] ?? held))
	}
	return [...new Set([...grants, parentOf(grant), NEEDS[grant] ?? grant, ...changed])]
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
	const parts = AREAS.flatMap((area) => {
		if (!selection.grants.includes(area)) {
			return []
		}
		return [selection.grants.includes(READ_ONLY[area].write) ? GRANT_LABELS[area] : READ_ONLY[area].label]
	})
	parts.push(...SERVICES.filter((service) => selection.grants.includes(service)).map((service) => GRANT_LABELS[service]))
	parts.push(...GRANTABLE_APPS.filter((app) => selection.apps.includes(app.id)).map(appLabel))
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
 * @param app An app the app password can be limited to
 */
export function appLabel(app: IGrantableApp): string {
	// TRANSLATORS {app} is an app name, e.g. "Activity". The app password then reads that app's data from all other apps
	return app.acrossApps ? t('settings', '{app} across all apps', { app: app.name }) : app.name
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
