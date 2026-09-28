/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface BaseTag {
	id?: number
	userVisible: boolean
	userAssignable: boolean
	readonly canAssign: boolean // Computed server-side
	etag?: string
	color?: string
}

export type Tag = BaseTag & {
	displayName: string
}

export type TagWithId = Required<Tag>

export type ServerTag = BaseTag & {
	name: string
}

export type ServerTagWithId = Required<ServerTag>
