/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { User } from '@nextcloud/cypress'
import { openSharingPanel } from '../FilesSharingUtils.ts'

export interface ShareContext {
	user: User
	url?: string
}

const defaultShareContext: ShareContext = {
	user: {} as User,
	url: undefined,
}

/**
 * Retrieves the URL of the share.
 * Throws an error if the share context is not initialized properly.
 *
 * @param context The current share context (defaults to `defaultShareContext` if not provided).
 * @return The share URL.
 * @throws Error if the share context has no URL.
 */
export function getShareUrl(context: ShareContext = defaultShareContext): string {
	if (!context.url) {
		throw new Error('Share context is not properly initialized with a URL.')
	}
	return context.url
}

/**
 * Setup the available data
 * @param context The current share context
 * @param shareName The name of the shared folder
 */
export function setupData(context: ShareContext, shareName: string): void {
	cy.mkdir(context.user, `/${shareName}`)
	cy.mkdir(context.user, `/${shareName}/subfolder`)
	cy.uploadContent(context.user, new Blob(['<content>foo</content>']), 'text/plain', `/${shareName}/foo.txt`)
	cy.uploadContent(context.user, new Blob(['<content>bar</content>']), 'text/plain', `/${shareName}/subfolder/bar.txt`)
}

/**
 * Create a public link share
 * @param context The current share context
 * @param shareName The name of the shared folder
 */
export function createShare(context: ShareContext, shareName: string) {
	cy.login(context.user)
	cy.visit('/apps/files') // Open the files app
	openSharingPanel(shareName) // Open the sharing sidebar

	cy.intercept('POST', '**/ocs/v2.php/apps/files_sharing/api/v1/shares').as('createShare')
	cy.findByRole('button', { name: 'Create a new share link' }).click()

	// Extract the share link
	return cy.wait('@createShare')
		.should(({ response }) => {
			expect(response?.statusCode).to.eq(200)
			const url = response?.body?.ocs?.data?.url
			expect(url).to.match(/^https?:\/\//)
			context.url = url
		})
		.then(() => cy.wrap(context.url))
}

/**
 * Adjust share permissions to be editable
 */
function adjustSharePermission(): void {
	cy.findByRole('list', { name: 'Link shares' })
		.findAllByRole('listitem')
		.first()
		.findByRole('button', { name: /Actions/i })
		.click()
	cy.findByRole('menuitem', { name: /Customize link/i }).click()

	cy.get('[data-cy-files-sharing-share-permissions-bundle]').should('be.visible')
	cy.get('[data-cy-files-sharing-share-permissions-bundle="upload-edit"]').click()

	cy.intercept('PUT', '**/ocs/v2.php/apps/files_sharing/api/v1/shares/*').as('updateShare')
	cy.findByRole('button', { name: 'Update share' }).click()
	cy.wait('@updateShare').its('response.statusCode').should('eq', 200)
}

/**
 * Setup a public share and backup the state.
 * If the setup was already done in another run, the state will be restored.
 *
 * @param shareName The name of the shared folder
 * @return The URL of the share
 */
export function setupPublicShare(shareName = 'shared'): Cypress.Chainable<string> {

	return cy.task('getVariable', { key: 'public-share-data' }).then((data) => {
		// Leave dataSnapshot part unchanged
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		const { dataSnapshot, shareUrl } = data as any || {}
		if (dataSnapshot) {
			cy.restoreState(dataSnapshot)
			defaultShareContext.url = shareUrl
			return cy.wrap(shareUrl as string)
		} else {
			const shareData: Record<string, unknown> = {}
			return cy.createRandomUser()
				.then((user) => {
					defaultShareContext.user = user
				})
				.then(() => setupData(defaultShareContext, shareName))
				.then(() => createShare(defaultShareContext, shareName))
				.then((url) => {
					shareData.shareUrl = url
				})
				.then(() => adjustSharePermission())
				.then(() =>
					cy.saveState().then((snapshot) => {
						shareData.dataSnapshot = snapshot
					}),
				)
				.then(() => cy.task('setVariable', { key: 'public-share-data', value: shareData }))
				.then(() => cy.log(`Public share setup, URL: ${shareData.shareUrl}`))
				.then(() => cy.wrap(defaultShareContext.url))
		}
	})
}
