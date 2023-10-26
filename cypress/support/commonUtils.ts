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

/**
 * Helper function ensure users and groups in this tests have a clean state
 * Deletes all users (except admin) and groups
 */
export function clearState() {
	// cleanup ignoring any failures
	cy.runOccCommand('group:list --output=json').then(($result) => {
		const groups = Object.keys(JSON.parse($result.stdout)).filter((name) => name !== 'admin')
		groups.forEach((groupID) => cy.runOccCommand(`group:delete '${groupID}'`))
	})

	cy.runOccCommand('user:list --output=json').then(($result) => {
		const users = Object.keys(JSON.parse($result.stdout)).filter((name) => name !== 'admin')
		users.forEach((userID) => cy.runOccCommand(`user:delete '${userID}'`))
	})
}
