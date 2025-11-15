/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server/cypress'

export const admin = new User('admin', 'admin')

/**
 * Make a user subadmin of a group.
 *
 * @param user - The user to make subadmin
 * @param group - The group the user should be subadmin of
 */
export function makeSubAdmin(user: User, group: string): void {
	cy.request({
		url: `${Cypress.config('baseUrl')!.replace('/index.php', '')}/ocs/v2.php/cloud/users/${user.userId}/subadmins`,
		method: 'POST',
		auth: {
			user: admin.userId,
			password: admin.userId,
		},
		headers: {
			'OCS-ApiRequest': 'true',
		},
		body: {
			groupid: group,
		},
	})
}
