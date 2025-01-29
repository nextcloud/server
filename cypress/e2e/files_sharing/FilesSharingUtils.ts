/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/* eslint-disable jsdoc/require-jsdoc */
import { triggerActionForFile } from '../files/FilesUtils'

export interface ShareSetting {
	read: boolean
	update: boolean
	delete: boolean
	create: boolean
	share: boolean
	download: boolean
	note: string
	expiryDate: Date
}

export function createShare(fileName: string, username: string, shareSettings: Partial<ShareSetting> = {}) {
	openSharingPanel(fileName)

	cy.get('#app-sidebar-vue').within(() => {
		cy.get('#sharing-search-input').clear()
		cy.intercept({ times: 1, method: 'GET', url: '**/apps/files_sharing/api/v1/sharees?*' }).as('userSearch')
		cy.get('#sharing-search-input').type(username)
		cy.wait('@userSearch')
	})

	cy.get(`[user="${username}"]`).click()

	// HACK: Save the share and then update it, as permissions changes are currently not saved for new share.
	cy.get('[data-cy-files-sharing-share-editor-action="save"]').click({ scrollBehavior: 'nearest' })
	updateShare(fileName, 0, shareSettings)
}

export function openSharingDetails(index: number) {
	cy.get('#app-sidebar-vue').within(() => {
		cy.get('[data-cy-files-sharing-share-actions]').eq(index).click()
		cy.get('[data-cy-files-sharing-share-permissions-bundle="custom"]').click()
	})
}

export function updateShare(fileName: string, index: number, shareSettings: Partial<ShareSetting> = {}) {
	openSharingPanel(fileName)
	openSharingDetails(index)

	cy.intercept({ times: 1, method: 'PUT', url: '**/apps/files_sharing/api/v1/shares/*' }).as('updateShare')

	cy.get('#app-sidebar-vue').within(() => {
		if (shareSettings.download !== undefined) {
			cy.get('[data-cy-files-sharing-share-permissions-checkbox="download"]').find('input').as('downloadCheckbox')
			if (shareSettings.download) {
				// Force:true because the checkbox is hidden by the pretty UI.
				cy.get('@downloadCheckbox').check({ force: true, scrollBehavior: 'nearest' })
			} else {
				// Force:true because the checkbox is hidden by the pretty UI.
				cy.get('@downloadCheckbox').uncheck({ force: true, scrollBehavior: 'nearest' })
			}
		}

		if (shareSettings.read !== undefined) {
			cy.get('[data-cy-files-sharing-share-permissions-checkbox="read"]').find('input').as('readCheckbox')
			if (shareSettings.read) {
				// Force:true because the checkbox is hidden by the pretty UI.
				cy.get('@readCheckbox').check({ force: true, scrollBehavior: 'nearest' })
			} else {
				// Force:true because the checkbox is hidden by the pretty UI.
				cy.get('@readCheckbox').uncheck({ force: true, scrollBehavior: 'nearest' })
			}
		}

		if (shareSettings.update !== undefined) {
			cy.get('[data-cy-files-sharing-share-permissions-checkbox="update"]').find('input').as('updateCheckbox')
			if (shareSettings.update) {
				// Force:true because the checkbox is hidden by the pretty UI.
				cy.get('@updateCheckbox').check({ force: true, scrollBehavior: 'nearest' })
			} else {
				// Force:true because the checkbox is hidden by the pretty UI.
				cy.get('@updateCheckbox').uncheck({ force: true, scrollBehavior: 'nearest' })
			}
		}

		if (shareSettings.create !== undefined) {
			cy.get('[data-cy-files-sharing-share-permissions-checkbox="create"]').find('input').as('createCheckbox')
			if (shareSettings.create) {
				// Force:true because the checkbox is hidden by the pretty UI.
				cy.get('@createCheckbox').check({ force: true, scrollBehavior: 'nearest' })
			} else {
				// Force:true because the checkbox is hidden by the pretty UI.
				cy.get('@createCheckbox').uncheck({ force: true, scrollBehavior: 'nearest' })
			}
		}

		if (shareSettings.delete !== undefined) {
			cy.get('[data-cy-files-sharing-share-permissions-checkbox="delete"]').find('input').as('deleteCheckbox')
			if (shareSettings.delete) {
				// Force:true because the checkbox is hidden by the pretty UI.
				cy.get('@deleteCheckbox').check({ force: true, scrollBehavior: 'nearest' })
			} else {
				// Force:true because the checkbox is hidden by the pretty UI.
				cy.get('@deleteCheckbox').uncheck({ force: true, scrollBehavior: 'nearest' })
			}
		}

		if (shareSettings.note !== undefined) {
			cy.findByRole('checkbox', { name: /note to recipient/i }).check({ force: true, scrollBehavior: 'nearest' })
			cy.findByRole('textbox', { name: /note to recipient/i }).type(shareSettings.note)
		}

		if (shareSettings.expiryDate !== undefined) {
			cy.findByRole('checkbox', { name: /expiration date/i })
				.check({ force: true, scrollBehavior: 'nearest' })
			cy.get('#share-date-picker')
				.type(`${shareSettings.expiryDate.getFullYear()}-${String(shareSettings.expiryDate.getMonth() + 1).padStart(2, '0')}-${String(shareSettings.expiryDate.getDate()).padStart(2, '0')}`)
		}

		cy.get('[data-cy-files-sharing-share-editor-action="save"]').click({ scrollBehavior: 'nearest' })

		cy.wait('@updateShare')
	})
	// close all toasts
	cy.get('.toast-success').findAllByRole('button').click({ force: true, multiple: true })
}

export function openSharingPanel(fileName: string) {
	triggerActionForFile(fileName, 'details')

	cy.get('#app-sidebar-vue')
		.get('[aria-controls="tab-sharing"]')
		.click()
}

type FileRequestOptions = {
	label?: string
	note?: string
	password?: string
	/* YYYY-MM-DD format */
	expiration?: string
}

/**
 * Create a file request for a folder
 * @param path The path of the folder, leading slash is required
 * @param options The options for the file request
 */
export const createFileRequest = (path: string, options: FileRequestOptions = {}) => {
	if (!path.startsWith('/')) {
		throw new Error('Path must start with a slash')
	}

	// Navigate to the folder
	cy.visit('/apps/files/files?dir=' + path)

	// Open the file request dialog
	cy.get('[data-cy-upload-picker] .action-item__menutoggle').first().click()
	cy.contains('.upload-picker__menu-entry button', 'Create file request').click()
	cy.get('[data-cy-file-request-dialog]').should('be.visible')

	// Check and fill the first page options
	cy.get('[data-cy-file-request-dialog-fieldset="label"]').should('be.visible')
	cy.get('[data-cy-file-request-dialog-fieldset="destination"]').should('be.visible')
	cy.get('[data-cy-file-request-dialog-fieldset="note"]').should('be.visible')

	cy.get('[data-cy-file-request-dialog-fieldset="destination"] input').should('contain.value', path)
	if (options.label) {
		cy.get('[data-cy-file-request-dialog-fieldset="label"] input').type(`{selectall}${options.label}`)
	}
	if (options.note) {
		cy.get('[data-cy-file-request-dialog-fieldset="note"] textarea').type(`{selectall}${options.note}`)
	}

	// Go to the next page
	cy.get('[data-cy-file-request-dialog-controls="next"]').click()
	cy.get('[data-cy-file-request-dialog-fieldset="expiration"] input[type="checkbox"]').should('exist')
	cy.get('[data-cy-file-request-dialog-fieldset="expiration"] input[type="date"]').should('not.exist')
	cy.get('[data-cy-file-request-dialog-fieldset="password"] input[type="checkbox"]').should('exist')
	cy.get('[data-cy-file-request-dialog-fieldset="password"] input[type="password"]').should('not.exist')
	if (options.expiration) {
		cy.get('[data-cy-file-request-dialog-fieldset="expiration"] input[type="checkbox"]').check({ force: true })
		cy.get('[data-cy-file-request-dialog-fieldset="expiration"] input[type="date"]').type(`{selectall}${options.expiration}`)
	}
	if (options.password) {
		cy.get('[data-cy-file-request-dialog-fieldset="password"] input[type="checkbox"]').check({ force: true })
		cy.get('[data-cy-file-request-dialog-fieldset="password"] input[type="password"]').type(`{selectall}${options.password}`)
	}

	// Create the file request
	cy.get('[data-cy-file-request-dialog-controls="next"]').click()

	// Get the file request URL
	cy.get('[data-cy-file-request-dialog-fieldset="link"]').then(($link) => {
		const url = $link.val()
		cy.log(`File request URL: ${url}`)
		cy.wrap(url).as('fileRequestUrl')
	})

	// Close
	cy.get('[data-cy-file-request-dialog-controls="finish"]').click()
}
