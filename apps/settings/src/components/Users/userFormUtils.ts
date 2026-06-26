/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IGroup } from '../../views/user-types.d.ts'

import { formatFileSize, parseFileSize } from '@nextcloud/files'
import { unlimitedQuota } from '../../utils/userUtils.ts'

interface QuotaOption {
	id: string
	label: string
}

interface LanguageOption {
	code: string
	name: string
}

interface FormData {
	username: string
	displayName: string
	password: string
	email: string
	groups: IGroup[]
	subadminGroups: IGroup[]
	quota: QuotaOption
	language: LanguageOption
	manager: string | { id: string, displayname?: string }
}

/**
 * Resolves the user's language code to a { code, name } object.
 *
 * @param user The user store object
 * @param serverLanguages Server language configuration
 * @return Language object with code and name
 */
export function resolveLanguage(user, serverLanguages): LanguageOption {
	if (!user.language || user.language === '') {
		return { code: '', name: '' }
	}
	const allLangs = [
		...(serverLanguages?.commonLanguages ?? []),
		...(serverLanguages?.otherLanguages ?? []),
	]
	const match = allLangs.find((lang) => lang.code === user.language)
	if (match) {
		return match
	}
	return { code: user.language, name: user.language }
}

/**
 * Maps a user store object to the flat, API-aligned shape used by the form.
 * Keeps a clean separation between the store model (e.g. `user.displayname`,
 * `user.quota.quota`) and the form model (e.g. `displayName`, `quota`).
 *
 * @param user The user store object
 * @param allGroups All available groups from the store
 * @param quotaOptions Quota preset options
 * @param serverLanguages Server language configuration
 * @return Form-ready data object
 */
export function userToFormData(user, allGroups, quotaOptions, serverLanguages) {
	const groups = user.groups
		.map((id) => allGroups.find((g) => g.id === id))
		.filter(Boolean)

	const subadminGroups = (user.subadmin ?? [])
		.map((id) => allGroups.find((g) => g.id === id))
		.filter(Boolean)

	let quota
	if (user.quota?.quota >= 0) {
		const label = formatFileSize(user.quota.quota)
		quota = quotaOptions.find((q) => q.id === label) ?? { id: label, label }
	} else if (user.quota?.quota === 'default') {
		quota = quotaOptions[0]
	} else {
		quota = unlimitedQuota
	}

	return {
		username: user.id,
		displayName: user.displayname ?? '',
		password: '',
		email: user.email ?? '',
		groups,
		subadminGroups,
		quota,
		language: resolveLanguage(user, serverLanguages),
		manager: user.manager ?? '',
	}
}

/**
 * Generic shallow diff between initial and current form data.
 * Returns only fields that changed, with API-ready values.
 *
 * @param initial Snapshot of form data at mount time
 * @param current Current form data state
 * @return Changed fields with API-ready values
 */
export function diffPayload(initial: FormData, current: FormData) {
	const payload: Record<string, string | string[]> = {}

	if (current.displayName !== initial.displayName) {
		payload.displayName = current.displayName
	}
	if (current.password !== '') {
		payload.password = current.password
	}
	if (current.email !== initial.email) {
		payload.email = current.email
	}
	if (current.quota.id !== initial.quota.id) {
		payload.quota = current.quota.id
	}
	if (current.language.code !== initial.language.code) {
		payload.language = current.language.code
	}
	const currentManagerId = typeof current.manager === 'object' ? (current.manager.id ?? '') : current.manager
	const initialManagerId = typeof initial.manager === 'object' ? (initial.manager.id ?? '') : initial.manager
	if (currentManagerId !== initialManagerId) {
		payload.manager = currentManagerId
	}

	const currentGroupIds = current.groups.map((g) => g.id).sort()
	const initialGroupIds = initial.groups.map((g) => g.id).sort()
	if (JSON.stringify(currentGroupIds) !== JSON.stringify(initialGroupIds)) {
		payload.groups = currentGroupIds
	}

	const currentSubadminIds = current.subadminGroups.map((g) => g.id).sort()
	const initialSubadminIds = initial.subadminGroups.map((g) => g.id).sort()
	if (JSON.stringify(currentSubadminIds) !== JSON.stringify(initialSubadminIds)) {
		payload.subadminGroups = currentSubadminIds
	}

	return payload
}

/**
 * Parses and normalizes a user-entered quota string into a quota option.
 * Returns the fallback option if the input is invalid.
 *
 * @param quota Raw quota string entered by the user (e.g. "4 MB")
 * @param fallback Fallback option when input is invalid
 * @param fallback.id Fallback option identifier
 * @param fallback.label Fallback option display label
 * @return Normalized quota option with id and label
 */
export function validateQuota(quota: string, fallback: { id: string, label: string }) {
	const parsed = parseFileSize(quota, true)
	if (parsed !== null && parsed >= 0) {
		const label = formatFileSize(parsed)
		return { id: label, label }
	}
	return fallback
}

/**
 * Filter function for the language NcSelect. Handles grouped options
 * (section headers with nested languages) and plain language entries.
 *
 * @param option The select option being filtered
 * @param option.languages Nested languages for group headers
 * @param label The option's display label
 * @param search The user's search string
 * @return Whether the option matches the search
 */
export function languageFilterBy(option: { languages?: Array<{ name: string }> }, label: string, search: string): boolean {
	if (option.languages) {
		return option.languages.some(({ name }) => name.toLocaleLowerCase().includes(search.toLocaleLowerCase()))
	}
	return (label || '').toLocaleLowerCase().includes(search.toLocaleLowerCase())
}
