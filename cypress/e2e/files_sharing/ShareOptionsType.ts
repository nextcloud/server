/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export type ShareOptions = {
    enforcePassword?: boolean
    enforceExpirationDate?: boolean
    alwaysAskForPassword?: boolean
    defaultExpirationDateSet?: boolean
}

export const defaultShareOptions: ShareOptions = {
	enforcePassword: false,
	enforceExpirationDate: false,
	alwaysAskForPassword: false,
	defaultExpirationDateSet: false,
}
