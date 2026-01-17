/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRowForFile, triggerActionForFile } from '../files/FilesUtils.ts'

export function addTagToFile(fileName: string, newTag: string): void {
	getRowForFile(fileName).should('be.visible')
	triggerActionForFile(fileName, 'systemtags:bulk')

	createNewTagInDialog(newTag)
}

export function createNewTagInDialog(newTag: string): void {
	cy.intercept('POST', '/remote.php/dav/systemtags').as('createTag')
	cy.intercept('PROPFIND', '/remote.php/dav/systemtags/*/files').as('getTagData')
	cy.intercept('PROPPATCH', '/remote.php/dav/systemtags/*/files').as('assignTagData')

	cy.get('[data-cy-systemtags-picker-input]').type(newTag)

	cy.get('[data-cy-systemtags-picker-tag]').should('have.length', 0)
	cy.get('[data-cy-systemtags-picker-button-create]').should('be.visible')
	cy.get('[data-cy-systemtags-picker-button-create]').click()

	cy.wait('@createTag')
	// Verify the new tag is selected by default
	cy.get('[data-cy-systemtags-picker-tag]').contains(newTag)
		.parents('[data-cy-systemtags-picker-tag]')
		.findByRole('checkbox', { hidden: true }).should('be.checked')

	// Apply changes
	cy.get('[data-cy-systemtags-picker-button-submit]').click()

	cy.wait('@assignTagData')
	cy.get('[data-cy-systemtags-picker]').should('not.exist')
}
