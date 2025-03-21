/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface Group {
	id: string
	displayname: string
	usercount: number
	disabled: number
	canAdd: boolean
	canRemove: boolean
}

export const formatGroup = (group: Group) => ({
	id: group.id,
	name: group.displayname,
	usercount: group.usercount,
	disabled: group.disabled,
	canAdd: group.canAdd,
	canRemove: group.canRemove,
})
