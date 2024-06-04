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
	share: boolean
	download: boolean
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

export function updateShare(fileName: string, index: number, shareSettings: Partial<ShareSetting> = {}) {
	openSharingPanel(fileName)

	cy.get('#app-sidebar-vue').within(() => {
		cy.get('[data-cy-files-sharing-share-actions]').eq(index).click()
		cy.get('[data-cy-files-sharing-share-permissions-bundle="custom"]').click()

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

		cy.get('[data-cy-files-sharing-share-editor-action="save"]').click({ scrollBehavior: 'nearest' })
	})
}

export function openSharingPanel(fileName: string) {
	triggerActionForFile(fileName, 'details')

	cy.get('#app-sidebar-vue')
		.get('[aria-controls="tab-sharing"]')
		.click()
}
