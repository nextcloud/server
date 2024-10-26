/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
export interface IGroup {
	id: string
	name: string

	/**
	 * Overall user count
	 */
	usercount: number

	/**
	 * Number of disabled users
	 */
	disabled: number
}
