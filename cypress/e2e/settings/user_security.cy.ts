/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/cypress'

const admin = new User('admin', 'admin')
const secDevice = 'Secondary test device'
const secDeviceEdited = 'Edited test device'

describe('Settings: Edit and delete user sessions', function() {
	beforeEach(function() {
		cy.logout()
		cy.visit('/login')

		// Create a secondary session on same user with different User-Agent
		cy.request('/csrftoken').then(({ body }) => {
			const requestToken = body.token
			cy.request({
				method: 'POST',
				url: '/login',
				body: {
					user: admin.userId,
					password: admin.password,
					requesttoken: requestToken,
				},
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
					'User-Agent': secDevice,
				},
				followRedirect: false,
			}).then(() => {
				// Forget secondary session to leave it open
				cy.clearAllCookies()
			})
		})
	})

	it('Can revoke a session', function() {
		cy.login(admin)
		cy.visit('/settings/user/security')

		// ensure secondary device is present
		cy.get('tr.auth-token')
			.contains(secDevice)
			.should('exist')
		// open actions menu on device
		cy.contains('tr.auth-token', secDevice)
			.find('.auth-token__actions')
			.click()
		// revoke
		cy.get('button').contains('Revoke').click()
		cy.get('.modal-container button').contains('Yes').click({ force: true })
		cy.get('tr.auth-token')
			.contains(secDevice)
			.should('not.exist')
	})

	it('Can edit a device name', function() {
		cy.login(admin)
		cy.visit('/settings/user/security')

		// ensure secondary device is present
		cy.get('tr.auth-token')
			.contains(secDevice)
			.should('exist')
		// open actions menu on device
		cy.contains('tr.auth-token', secDevice)
			.find('.auth-token__actions')
			.click()
		// rename
		cy.get('button').contains('Rename').click()
		cy.get('.auth-token__name-form input[type="text"]').type(secDeviceEdited)
		cy.get('button[aria-label="Save new name"]').click()
		cy.get('.modal-container button').contains('Yes').click({ force: true })
		// make sure rename worked
		cy.get('tr.auth-token')
			.contains(secDeviceEdited)
			.should('exist')
		cy.get('tr.auth-token')
			.contains(secDevice)
			.should('not.exist')
	})

	it('Can wipe a device', function() {
		cy.login(admin)
		cy.visit('/settings/user/security')

		// ensure secondary device is present
		cy.get('tr.auth-token')
			.contains(secDevice)
			.should('exist')
		// open actions menu on device
		cy.contains('tr.auth-token', secDevice)
			.find('.auth-token__actions')
			.click()
		// mark for wipe
		cy.get('button').contains('Wipe device').click()
		cy.get('.modal-container button').contains('Yes').click({ force: true })
		// make sure wipe was selected
		cy.contains('tr.auth-token', secDevice)
			.contains('(Marked for remote wipe)')
			.should('exist')
	})
})
