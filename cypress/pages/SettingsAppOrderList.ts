/*!
 * SPDX-FileCopyrightText: Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export class SettingsAppOrderList {
	getAppOrderList() {
		return cy.findByRole('list', { name: 'Navigation bar app order' })
	}

	assertAppOrder(expectedAppOrder: string[]) {
		this.getAppOrderList()
			.findAllByRole('listitem')
			.should('have.length', expectedAppOrder.length)
			.each((element, index) => expect(element).to.contain.text(expectedAppOrder[index]!))
	}

	getAppEntryByName(appName: string) {
		return this.getAppOrderList()
			.findAllByRole('listitem')
			.filter((_, el) => el.textContent.trim() === appName)
	}

	getUpButtonForApp(appName: string) {
		return this.getAppEntryByName(appName).findByRole('button', { name: 'Move up', hidden: true })
	}

	getDownButtonForApp(appName: string) {
		return this.getAppEntryByName(appName).findByRole('button', { name: 'Move down', hidden: true })
	}

	getResetButton() {
		return cy.findByRole('button', { name: 'Reset default app order', hidden: true })
	}

	interceptAppOrder() {
		cy.intercept('POST', '/ocs/v2.php/apps/provisioning_api/api/v1/config/users/core/apporder').as('updateAppOrder')
	}

	waitForAppOrderUpdate() {
		cy.wait('@updateAppOrder')
	}
}
