/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { User } from '@nextcloud/cypress'
import { openSharingPanel } from '../FilesSharingUtils.ts'

let user: User
let url: string

/**
 * URL of the share
 */
export function getShareUrl() {
	if (url === undefined) {
		throw new Error('You need to setup the share first!')
	}
	return url
}

/**
 * Setup the available data
 * @param shareName The name of the shared folder
 */
function setupData(shareName: string) {
	cy.mkdir(user, `/${shareName}`)
	cy.mkdir(user, `/${shareName}/subfolder`)
	cy.uploadContent(user, new Blob(['<content>foo</content>']), 'text/plain', `/${shareName}/foo.txt`)
	cy.uploadContent(user, new Blob(['<content>bar</content>']), 'text/plain', `/${shareName}/subfolder/bar.txt`)
}

/**
 * Create a public link share
 * @param shareName The name of the shared folder
 */
function createShare(shareName: string) {
	cy.login(user)
	// open the files app
	cy.visit('/apps/files')
	// open the sidebar
	openSharingPanel(shareName)
	// create the share
	cy.intercept('POST', '**/ocs/v2.php/apps/files_sharing/api/v1/shares').as('createShare')
	cy.findByRole('button', { name: 'Create a new share link' })
		.click()

	// extract the link
	return cy.wait('@createShare')
		.should(({ response }) => {
			const { ocs } = response!.body
			url = ocs?.data.url
			expect(url).to.match(/^http:\/\//)
		})
		.then(() => cy.wrap(url))
}

/**
 * Adjust share permissions to be editable
 */
function adjustSharePermission() {
	// Update the share to be a file drop
	cy.findByRole('list', { name: 'Link shares' })
		.findAllByRole('listitem')
		.first()
		.findByRole('button', { name: /Actions/i })
		.click()
	cy.findByRole('menuitem', { name: /Customize link/i })
		.should('be.visible')
		.click()

	// Enable upload-edit
	cy.get('[data-cy-files-sharing-share-permissions-bundle]')
		.should('be.visible')
	cy.get('[data-cy-files-sharing-share-permissions-bundle="upload-edit"]')
		.click()
	// save changes
	cy.intercept('PUT', '**/ocs/v2.php/apps/files_sharing/api/v1/shares/*').as('updateShare')
	cy.findByRole('button', { name: 'Update share' })
		.click()
	cy.wait('@updateShare')
}

/**
 * Setup a public share and backup the state.
 * If the setup was already done in another run, the state will be restored.
 *
 * @return The URL of the share
 */
export function setupPublicShare(): Cypress.Chainable<string> {
	const shareName = 'shared'

	return cy.task('getVariable', { key: 'public-share-data' })
		.then((data) => {
			// eslint-disable-next-line @typescript-eslint/no-explicit-any
			const { dataSnapshot, shareUrl } = data as any || {}
			if (dataSnapshot) {
				cy.restoreState(dataSnapshot)
				url = shareUrl
				return cy.wrap(shareUrl as string)
			} else {
				const shareData: Record<string, unknown> = {}
				return cy.createRandomUser()
					.then(($user) => { user = $user })
					.then(() => setupData(shareName))
					.then(() => createShare(shareName))
					.then((value) => { shareData.shareUrl = value })
					.then(() => adjustSharePermission())
					.then(() => cy.saveState().then((value) => { shareData.dataSnapshot = value }))
					.then(() => cy.task('setVariable', { key: 'public-share-data', value: shareData }))
					.then(() => cy.log(`Public share setup, URL: ${shareData.shareUrl}`))
					.then(() => cy.wrap(url))
			}
		})
}
