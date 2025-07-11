/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
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
