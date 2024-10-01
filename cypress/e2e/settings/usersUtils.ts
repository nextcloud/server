/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'

/**
 * Assert that `element` does not exist or is not visible
 * Useful in cases such as when NcModal is opened/closed rapidly
 * @param element Element that is inspected
 */
export function assertNotExistOrNotVisible(element: JQuery<HTMLElement>) {
	const doesNotExist = element.length === 0
	const isNotVisible = !element.is(':visible')

	// eslint-disable-next-line no-unused-expressions
	expect(doesNotExist || isNotVisible, 'does not exist or is not visible').to.be.true
}

/**
 * Get the settings users list
 * @return Cypress chainable object
 */
export function getUserList() {
	return cy.get('[data-cy-user-list]')
}

/**
 * Get the row entry for given userId within the settings users list
 *
 * @param userId the user to query
 * @return Cypress chainable object
 */
export function getUserListRow(userId: string) {
	return getUserList().find(`[data-cy-user-row="${userId}"]`)
}

export function waitLoading(selector: string) {
	// We need to make sure the element is loading, otherwise the "done loading" will succeed even if we did not start loading.
	// But Cypress might also be simply too slow to catch the loading phase. Thats why we need to wait in this case.
	// eslint-disable-next-line cypress/no-unnecessary-waiting
	cy.get(`${selector}[data-loading]`).if().should('exist').else().wait(1000)
	// https://github.com/NoriSte/cypress-wait-until/issues/75#issuecomment-572685623
	cy.waitUntil(() => Cypress.$(selector).length > 0 && !Cypress.$(selector).attr('data-loading')?.length, { timeout: 10000 })
}

/**
 * Toggle the edit button of the user row
 * @param user The user row to edit
 * @param toEdit True if it should be switch to edit mode, false to switch to read-only
 */
export function toggleEditButton(user: User, toEdit = true) {
	// see that the list of users contains the user
	getUserListRow(user.userId).should('exist')
		// toggle the edit mode for the user
		.find('[data-cy-user-list-cell-actions]')
		.find(`[data-cy-user-list-action-toggle-edit="${!toEdit}"]`)
		.if()
		.click({ force: true })
		.else()
		// otherwise ensure the button is already in edit mode
		.then(() => getUserListRow(user.userId)
			.find(`[data-cy-user-list-action-toggle-edit="${toEdit}"]`)
			.should('exist'),
		)
}

/**
 * Handle the confirm password dialog (if needed)
 * @param adminPassword The admin password for the dialog
 */
export function handlePasswordConfirmation(adminPassword = 'admin') {
	const handleModal = (context: Cypress.Chainable) => {
		return context.contains('.modal-container', 'Confirm your password')
			.if()
			.within(() => {
				cy.get('input[type="password"]').type(adminPassword)
				cy.get('button').contains('Confirm').click()
			})
	}

	return cy.get('body')
		.if()
		.then(() => handleModal(cy.get('body')))
		.else()
		// Handle if inside a cy.within
		.root().closest('body')
		.then(($body) => handleModal(cy.wrap($body)))
}
