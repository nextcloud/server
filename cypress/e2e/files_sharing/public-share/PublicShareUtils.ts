/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { User } from '@nextcloud/cypress'
import type { ShareOptions } from '../ShareOptionsType.ts'
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
		throw new Error('You need to setup the share first!')
	}
	return context.url
}

/**
 * Setup the available data
 * @param user The current share context
 * @param shareName The name of the shared folder
 */
export function setupData(user: User, shareName: string): void {
	cy.mkdir(user, `/${shareName}`)
	cy.mkdir(user, `/${shareName}/subfolder`)
	cy.uploadContent(user, new Blob(['<content>foo</content>']), 'text/plain', `/${shareName}/foo.txt`)
	cy.uploadContent(user, new Blob(['<content>bar</content>']), 'text/plain', `/${shareName}/subfolder/bar.txt`)
}

/**
 * Check the password state based on enforcement and default presence.
 *
 * @param enforced Whether the password is enforced.
 * @param alwaysAskForPassword Wether the password should always be asked for.
 */
function checkPasswordState(enforced: boolean, alwaysAskForPassword: boolean) {
	if (enforced) {
		cy.contains('Password protection (enforced)').should('exist')
	} else if (alwaysAskForPassword) {
		cy.contains('Password protection').should('exist')
	}
	cy.contains('Enter a password')
		.should('exist')
		.and('not.be.disabled')
}

/**
 * Check the expiration date state based on enforcement and default presence.
 *
 * @param enforced Whether the expiration date is enforced.
 * @param hasDefault Whether a default expiration date is set.
 */
function checkExpirationDateState(enforced: boolean, hasDefault: boolean) {
	if (enforced) {
		cy.contains('Enable link expiration (enforced)').should('exist')
	} else if (hasDefault) {
		cy.contains('Enable link expiration').should('exist')
	}
	cy.contains('Enter expiration date')
		.should('exist')
		.and('not.be.disabled')
	cy.get('input[data-cy-files-sharing-expiration-date-input]').should('exist')
	cy.get('input[data-cy-files-sharing-expiration-date-input]')
		.invoke('val')
		.then((val) => {
			// eslint-disable-next-line no-unused-expressions
			expect(val).to.not.be.undefined

			const inputDate = new Date(typeof val === 'number' ? val : String(val))
			const expectedDate = new Date()
			expectedDate.setDate(expectedDate.getDate() + 2)
			expect(inputDate.toDateString()).to.eq(expectedDate.toDateString())
		})

}

/**
 * Create a public link share
 * @param context The current share context
 * @param shareName The name of the shared folder
 * @param options The share options
 */
export function createLinkShare(context: ShareContext, shareName: string, options: ShareOptions | null = null): Cypress.Chainable<string> {
	cy.login(context.user)
	cy.visit('/apps/files')
	openSharingPanel(shareName)

	cy.intercept('POST', '**/ocs/v2.php/apps/files_sharing/api/v1/shares').as('createLinkShare')
	cy.findByRole('button', { name: 'Create a new share link' }).click()
	// Conduct optional checks based on the provided options
	if (options) {
		cy.get('.sharing-entry__actions').should('be.visible') // Wait for the dialog to open
		checkPasswordState(options.enforcePassword ?? false, options.alwaysAskForPassword ?? false)
		checkExpirationDateState(options.enforceExpirationDate ?? false, options.defaultExpirationDateSet ?? false)
		cy.findByRole('button', { name: 'Create share' }).click()
	}

	return cy.wait('@createLinkShare')
		.should(({ response }) => {
			expect(response?.statusCode).to.eq(200)
			const url = response?.body?.ocs?.data?.url
			expect(url).to.match(/^https?:\/\//)
			context.url = url
		})
		.then(() => cy.wrap(context.url as string))
}

/**
 * open link share details for specific index
 *
 * @param index
 */
export function openLinkShareDetails(index: number) {
	cy.findByRole('list', { name: 'Link shares' })
		.findAllByRole('listitem')
		.eq(index)
		.findByRole('button', { name: /Actions/i })
		.click()
	cy.findByRole('menuitem', { name: /Customize link/i }).click()
}

/**
 * Adjust share permissions to be editable
 */
function adjustSharePermission(): void {
	openLinkShareDetails(0)

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

	return cy.task('getVariable', { key: 'public-share-data' })
		.then((data) => {
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
					.then(() => setupData(defaultShareContext.user, shareName))
					.then(() => createLinkShare(defaultShareContext, shareName))
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
