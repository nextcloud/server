/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server/cypress'
import { clearState } from '../../support/commonUtils.ts'
import { handlePasswordConfirmation } from '../core-utils.ts'
import { openEditDialog, saveEditDialog } from './usersUtils.ts'

const admin = new User('admin', 'admin')

describe('Settings: User Manager Management', function() {
	let user: User
	let manager: User

	beforeEach(function() {
		clearState()
		cy.createRandomUser().then(($user) => {
			manager = $user
			return cy.createRandomUser()
		}).then(($user) => {
			user = $user
			cy.login(admin)
		})
	})

	it('Can assign a manager through the edit dialog', function() {
		cy.visit('/settings/users')

		openEditDialog(user)

		// Open the Manager NcSelect and type manager name
		cy.get('.edit-dialog [data-test="form"]').within(() => {
			cy.findByRole('combobox', { name: /Manager/i }).click({ force: true })
			cy.findByRole('combobox', { name: /Manager/i }).type(manager.userId)
		})

		// Select the manager from the floating dropdown
		cy.get('.vs__dropdown-menu').should('be.visible')
			.contains('li', manager.userId).click({ force: true })

		handlePasswordConfirmation(admin.password)
		saveEditDialog()

		cy.get('.toastify.toast-success').contains(/Account updated/i).should('exist')

		// Verify backend
		cy.getUserData(user).then(($result) => {
			expect($result.body).to.contain(`<manager>${manager.userId}</manager>`)
		})
	})

	it('Can remove a manager through the edit dialog', function() {
		// Set manager via backend first.
		// User::getManagerUids() decodes this with JSON_THROW_ON_ERROR, so we
		// must store a JSON array, matching what setManagerUids() writes.
		// Double-quotes are escaped because runOccCommand passes the command
		// through `bash -c "..."`, which would otherwise eat them.
		cy.runOccCommand(`user:setting '${user.userId}' settings manager '[\\"${manager.userId}\\"]'`)

		cy.visit('/settings/users')

		openEditDialog(user)

		// Clear the manager selection inside the dialog
		cy.get('.edit-dialog [data-test="form"]').within(() => {
			cy.get('.user-form__managers .vs__clear').click({ force: true })
		})

		handlePasswordConfirmation(admin.password)
		saveEditDialog()

		cy.get('.toastify.toast-success').contains(/Account updated/i).should('exist')

		// Verify backend
		cy.getUserData(user).then(($result) => {
			expect($result.body).to.not.contain(`<manager>${manager.userId}</manager>`)
			expect($result.body).to.contain('<manager></manager>')
		})
	})
})
