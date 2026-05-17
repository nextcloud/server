/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/cypress'

describe('Files default view', function() {
	const user = new User('admin', 'admin')

	before(function() {
		cy.login(user)
	})

	after(function() {
		cy.logout()
	})

	it('See the default files list', function() {
		cy.visit('/apps/files')
		cy.getFile('welcome.txt').should('contain', 'welcome .txt')
	})

	it('Take screenshot', function() {
		cy.screenshot()
	})
})
