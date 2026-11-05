/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** An admin settings section whose access can be delegated */
export interface DelegatedSetting {
	id: string
	class: string
	sectionName: string
}

export interface Group {
	gid: string
	displayName: string
}

/** A group allowed to access a delegated setting */
export interface AuthorizedGroup {
	group_id: string
	class: string
}
