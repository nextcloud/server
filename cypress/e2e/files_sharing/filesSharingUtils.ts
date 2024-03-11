/* eslint-disable jsdoc/require-jsdoc */
/**
 * @copyright Copyright (c) 2024 Louis Chemineau <louis@chmn.me>
 *
 * @author Louis Chemineau <louis@chmn.me>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

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
				cy.get('@downloadCheckbox').check({ force: true, scrollBehavior: 'nearest' })
			} else {
				cy.get('@downloadCheckbox').uncheck({ force: true, scrollBehavior: 'nearest' })
			}
		}

		if (shareSettings.read !== undefined) {
			cy.get('[data-cy-files-sharing-share-permissions-checkbox="read"]').find('input').as('readCheckbox')
			if (shareSettings.read) {
				cy.get('@readCheckbox').check({ force: true, scrollBehavior: 'nearest' })
			} else {
				cy.get('@readCheckbox').uncheck({ force: true, scrollBehavior: 'nearest' })
			}
		}

		if (shareSettings.update !== undefined) {
			cy.get('[data-cy-files-sharing-share-permissions-checkbox="update"]').find('input').as('updateCheckbox')
			if (shareSettings.update) {
				cy.get('@updateCheckbox').check({ force: true, scrollBehavior: 'nearest' })
			} else {
				cy.get('@updateCheckbox').uncheck({ force: true, scrollBehavior: 'nearest' })
			}
		}

		if (shareSettings.delete !== undefined) {
			cy.get('[data-cy-files-sharing-share-permissions-checkbox="delete"]').find('input').as('deleteCheckbox')
			if (shareSettings.delete) {
				cy.get('@deleteCheckbox').check({ force: true, scrollBehavior: 'nearest' })
			} else {
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
