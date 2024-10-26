/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export type ShareAttribute = {
	value: boolean|string|number|null|object|Array<unknown>
	key: string
	scope: string
}
