/*
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 */

/**
 * Get the viewer element
 */
export function getViewer() {
	return cy.get('body > .viewer')
}

/**
 * Get the viewer header element
 */
export function getViewerHeader() {
	return getViewer().find('.modal-header')
}

/**
 * Trigger the viewer action menu
 */
export function toggleViewerActions() {
	getViewerHeader()
		.findByRole('button', { name: 'Actions' })
		.click()
}

export function getViewerActionsMenu() {
	getViewerHeader()
		.findByRole('button', { name: 'Actions' })
		.as('actionsMenuButton')
	cy.get('@actionsMenuButton')
		.should('have.attr', 'aria-controls')
		.then((id) => {
			cy.get(`#${id}`).as('actionsMenu')
		})
	return cy.get('@actionsMenu')
}
