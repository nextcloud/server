/**
 * Get the header navigation bar
 */
export function getNextcloudHeader() {
	return cy.get('#header')
}

/**
 * Get user menu in the header navigation bar
 */
export function getNextcloudUserMenu() {
	return getNextcloudHeader().find('#user-menu')
}

/**
 * Get the user menu toggle in the header navigation bar
 */
export function getNextcloudUserMenuToggle() {
	return getNextcloudUserMenu().find('.header-menu__trigger').should('have.length', 1)
}
