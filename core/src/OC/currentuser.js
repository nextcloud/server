/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const rawUid = document
	.getElementsByTagName('head')[0]
	.getAttribute('data-user')
const displayName = document
	.getElementsByTagName('head')[0]
	.getAttribute('data-user-displayname')

export const currentUser = rawUid !== undefined ? rawUid : false

export const getCurrentUser = () => {
	return {
		uid: currentUser,
		displayName,
	}
}
