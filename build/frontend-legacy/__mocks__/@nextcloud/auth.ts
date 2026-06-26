/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export function getCurrentUser() {
	return {
		uid: 'test',
		displayName: 'Test',
		isAdmin: false,
	}
}

export function getRequestToken() {
	return 'test-token-1234'
}

export function onRequestTokenUpdate() {}
