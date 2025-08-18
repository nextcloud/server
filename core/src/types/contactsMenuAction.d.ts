/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface IContactsMenuAction {
	id: string
	icon: string
	label: string
	onClick: () => void | Promise<void>
}
