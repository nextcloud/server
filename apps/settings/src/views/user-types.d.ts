/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * A user as exposed by the (currently untyped) users Vuex store.
 *
 * This describes the fields the user-management components read. It is an
 * explicit assertion about the store's shape until the store itself is typed
 * (see the Vuex → TS store migration); tighten/replace it there.
 */
export interface IUser {
	/** Account id / login name */
	id: string

	/** Display name */
	displayname: string

	/** Primary email address, or null when unset */
	email: string | null

	/** Ids of the groups the user belongs to */
	groups: string[]

	/** Ids of the groups the user is a sub-admin of */
	subadmin: string[]

	/** Quota information; `quota` is intentionally loose (bytes or a keyword like 'default'/'none') */
	quota: {
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		quota: any
		used: number
	}

	/** Selected language code, '' when not set */
	language: string

	/** User backend identifier */
	backend: string

	/** Home storage path */
	storageLocation: string

	/** First login as a unix timestamp in seconds (0 = never, < 0 = unknown) */
	firstLoginTimestamp: number

	/** Last login as a unix timestamp in seconds */
	lastLoginTimestamp: number

	/** Manager account id */
	manager: string

	/** Whether the account is enabled */
	enabled: boolean

	/** Backend capability flags */
	backendCapabilities: {
		setPassword: boolean
	}

	/**
	 * The store exposes more fields than are enumerated here. Unknown keys
	 * resolve to `unknown` (not `any`), so typos still error on use.
	 */
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
