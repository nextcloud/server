/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { basename } from 'path'

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

/**
 * Install the test app
 */
export function installTestApp() {
	const testAppPath = 'cypress/fixtures/testapp'
	cy.runOccCommand('-V').then((output) => {
		const version = output.stdout.match(/(\d\d+)\.\d+\.\d+/)?.[1]
		cy.wrap(version).should('not.be.undefined')
		getContainerName()
			.then(containerName => {
				cy.exec(`docker cp '${testAppPath}' ${containerName}:/var/www/html/apps`, { log: true })
				cy.exec(`docker exec --workdir /var/www/html ${containerName} chown -R www-data:www-data /var/www/html/apps/testapp`)
			})
		cy.runCommand(`sed -i -e 's|-version=\\"[0-9]\\+|-version=\\"${version}|g' apps/testapp/appinfo/info.xml`)
		cy.runOccCommand('app:enable --force testapp')
	})
}

/**
 * Remove the test app
 */
export function uninstallTestApp() {
	cy.runOccCommand('app:remove testapp', { failOnNonZeroExit: false })
	cy.runCommand('rm -fr apps/testapp/appinfo/info.xml')
}

/**
 *
 */
export function getContainerName(): Cypress.Chainable<string> {
	return cy.exec('pwd')
		.then(({ stdout }) => {
			return cy.wrap(`nextcloud-cypress-tests_${basename(stdout).replace(' ', '')}`)
		})
}
