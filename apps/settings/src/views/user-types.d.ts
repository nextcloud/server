/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * The fields the user-management components read from the (still untyped) users
 * Vuex store. An explicit assertion of the store shape until the store is typed.
 */
export interface IUser {
	id: string
	displayname: string
	email: string | null
	groups: string[]
	subadmin: string[]
	// `quota` is intentionally loose: bytes or a keyword like 'default'/'none'.
	quota: {
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		quota: any
		used: number
	}
	language: string
	backend: string
	storageLocation: string
	// Unix timestamp in seconds; 0 = never, < 0 = unknown.
	firstLoginTimestamp: number
	lastLoginTimestamp: number
	manager: string
	enabled: boolean
	backendCapabilities: {
		setPassword: boolean
	}

	// The store exposes more fields than enumerated; unknown keys resolve to `unknown`, not `any`.
	[key: string]: unknown
}

export interface IGroup {
	/**
	 * Id
	 */
	id: string

	/**
	 * Display name
	 */
	name: string

	/**
	 * Overall user count
	 */
	usercount: number

	/**
	 * Number of disabled users
	 */
	disabled: number

	/**
	 * True if users can be added to this group
	 */
	canAdd?: boolean

	/**
	 * True if users can be removed from this group
	 */
	canRemove?: boolean
}
