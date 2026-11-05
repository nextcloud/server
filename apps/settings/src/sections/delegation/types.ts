/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface DelegatedSetting {
	id: string
	class: string
	sectionName: string
}

export interface Group {
	gid: string
	displayName: string
}

export interface AuthorizedGroup {
	group_id: string
	class: string
}
