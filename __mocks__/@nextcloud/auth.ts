/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
export const getCurrentUser = function() {
	return {
		uid: 'test',
		displayName: 'Test',
		isAdmin: false,
	}
}

export const getRequestToken = function() {
	return 'test-token-1234'
}

export const onRequestTokenUpdate = function() {}
