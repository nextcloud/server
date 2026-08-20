/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

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
		// @ts-expect-error we added this property in cypress.config.ts
		const containerName = Cypress.config('dockerContainerName')
		const version = output.stdout.match(/(\d\d+)\.\d+\.\d+/)?.[1]
		cy.wrap(version).should('not.be.undefined')

		// @nextcloud/e2e-test-server (0.5.0+) writes config/apps.config.php,
		// overriding any custom apps_paths (config/*.config.php files merge
		// alphabetically, later file wins) — occ only sees the writable apps
		// folder, which 0.5.1 renamed from apps_writable to apps-writable.
		cy.runCommand('test -d apps-writable && echo -n apps-writable || echo -n apps_writable').then(({ stdout }) => {
			const appsFolder = stdout.trim()
			// Fail here rather than with an appstore error further down if the
			// package ever stops providing a writable apps folder altogether.
			cy.runCommand(`test -d ${appsFolder}`)
			cy.exec(`docker cp '${testAppPath}' ${containerName}:/var/www/html/${appsFolder}`, { log: true })
			cy.exec(`docker exec --workdir /var/www/html ${containerName} chown -R www-data:www-data /var/www/html/${appsFolder}/testapp`)
			cy.runCommand(`sed -i -e 's|-version=\\"[0-9]\\+|-version=\\"${version}|g' ${appsFolder}/testapp/appinfo/info.xml`)
			cy.runOccCommand('app:enable --force testapp')
		})
	})
}

/**
 * Remove the test app
 */
export function uninstallTestApp() {
	cy.runOccCommand('app:remove testapp', { failOnNonZeroExit: false })
	cy.runCommand('rm -fr apps-writable/testapp apps_writable/testapp')
}
