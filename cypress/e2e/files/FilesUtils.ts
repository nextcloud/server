/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
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

import type { User } from '@nextcloud/cypress'

export const selectAllFiles = () => {
	cy.get('[data-cy-files-list-selection-checkbox]')
		.findByRole('checkbox', { checked: false })
		.click({ force: true })
}
export const deselectAllFiles = () => {
	cy.get('[data-cy-files-list-selection-checkbox]')
		.findByRole('checkbox', { checked: true })
		.click({ force: true })
}

export const getRowForFileId = (fileid: number) => cy.get(`[data-cy-files-list-row-fileid="${fileid}"]`)
export const getRowForFile = (filename: string) => cy.get(`[data-cy-files-list-row-name="${CSS.escape(filename)}"]`)

export const getActionsForFileId = (fileid: number) => getRowForFileId(fileid).find('[data-cy-files-list-row-actions]')
export const getActionsForFile = (filename: string) => getRowForFile(filename).find('[data-cy-files-list-row-actions]')

export const getActionButtonForFileId = (fileid: number) => getActionsForFileId(fileid).findByRole('button', { name: 'Actions' })
export const getActionButtonForFile = (filename: string) => getActionsForFile(filename).findByRole('button', { name: 'Actions' })

const searchForActionInRow = (row: JQuery<HTMLElement>, actionId: string): Cypress.Chainable<JQuery<HTMLElement>>  => {
	const action = row.find(`[data-cy-files-list-row-action="${CSS.escape(actionId)}"]`)
	if (action.length > 0) {
		cy.log('Found action in row')
		return cy.wrap(action)
	}

	// Else look in the action menu
	const menuButtonId = row.find('button[aria-controls]').attr('aria-controls')
	return cy.get(`#${menuButtonId} [data-cy-files-list-row-action="${CSS.escape(actionId)}"]`)
}

export const getActionEntryForFileId = (fileid: number, actionId: string): Cypress.Chainable<JQuery<HTMLElement>> => {
	// If we cannot find the action in the row, it might be in the action menu
	return getRowForFileId(fileid).should('be.visible')
		.then(row => searchForActionInRow(row, actionId))
}
export const getActionEntryForFile = (filename: string, actionId: string): Cypress.Chainable<JQuery<HTMLElement>> => {
	// If we cannot find the action in the row, it might be in the action menu
	return getRowForFile(filename).should('be.visible')
		.then(row => searchForActionInRow(row, actionId))
}

export const triggerActionForFileId = (fileid: number, actionId: string) => {
	// Even if it's inline, we open the action menu to get all actions visible
	getActionButtonForFileId(fileid).click({ force: true })
	getActionEntryForFileId(fileid, actionId)
		.find('button').last()
		.should('exist').click({ force: true })
}
export const triggerActionForFile = (filename: string, actionId: string) => {
	// Even if it's inline, we open the action menu to get all actions visible
	getActionButtonForFile(filename).click({ force: true })
	getActionEntryForFile(filename, actionId)
		.find('button').last()
		.should('exist').click({ force: true })
}

export const triggerInlineActionForFileId = (fileid: number, actionId: string) => {
	getActionsForFileId(fileid).find(`button[data-cy-files-list-row-action="${CSS.escape(actionId)}"]`).should('exist').click()
}
export const triggerInlineActionForFile = (filename: string, actionId: string) => {
	getActionsForFile(filename).find(`button[data-cy-files-list-row-action="${CSS.escape(actionId)}"]`).should('exist').click()
}

export const createFolder = (folderName: string) => {
	cy.intercept('MKCOL', /\/remote.php\/dav\/files\//).as('createFolder')

	// TODO: replace by proper data-cy selectors
	cy.get('[data-cy-upload-picker] .action-item__menutoggle').first().click()
	cy.contains('.upload-picker__menu-entry button', 'New folder').click()
	cy.get('[data-cy-files-new-node-dialog]').should('be.visible')
	cy.get('[data-cy-files-new-node-dialog-input]').type(`{selectall}${folderName}`)
	cy.get('[data-cy-files-new-node-dialog-submit]').click()

	cy.wait('@createFolder')

	getRowForFile(folderName).should('be.visible')
}

export const moveFile = (fileName: string, dirPath: string) => {
	getRowForFile(fileName).should('be.visible')
	triggerActionForFile(fileName, 'move-copy')

	cy.get('.file-picker').within(() => {
		// intercept the copy so we can wait for it
		cy.intercept('MOVE', /\/remote.php\/dav\/files\//).as('moveFile')

		if (dirPath === '/') {
			// select home folder
			cy.get('button[title="Home"]').should('be.visible').click()
			// click move
			cy.contains('button', 'Move').should('be.visible').click()
		} else if (dirPath === '.') {
			// click move
			cy.contains('button', 'Copy').should('be.visible').click()
		} else {
			const directories = dirPath.split('/')
			directories.forEach((directory) => {
				// select the folder
				cy.get(`[data-filename="${directory}"]`).should('be.visible').click()
			})

			// click move
			cy.contains('button', `Move to ${directories.at(-1)}`).should('be.visible').click()
		}

		cy.wait('@moveFile')
	})
}

export const copyFile = (fileName: string, dirPath: string) => {
	getRowForFile(fileName).should('be.visible')
	triggerActionForFile(fileName, 'move-copy')

	cy.get('.file-picker').within(() => {
		// intercept the copy so we can wait for it
		cy.intercept('COPY', /\/remote.php\/dav\/files\//).as('copyFile')

		if (dirPath === '/') {
			// select home folder
			cy.get('button[title="Home"]').should('be.visible').click()
			// click copy
			cy.contains('button', 'Copy').should('be.visible').click()
		} else if (dirPath === '.') {
			// click copy
			cy.contains('button', 'Copy').should('be.visible').click()
		} else {
			const directories = dirPath.split('/')
			directories.forEach((directory) => {
				// select the folder
				cy.get(`[data-filename="${CSS.escape(directory)}"]`).should('be.visible').click()
			})

			// click copy
			cy.contains('button', `Copy to ${directories.at(-1)}`).should('be.visible').click()
		}

		cy.wait('@copyFile')
	})
}

export const renameFile = (fileName: string, newFileName: string) => {
	getRowForFile(fileName)
	triggerActionForFile(fileName, 'rename')

	// intercept the move so we can wait for it
	cy.intercept('MOVE', /\/remote.php\/dav\/files\//).as('moveFile')

	getRowForFile(fileName).find('[data-cy-files-list-row-name] input').type(`{selectAll}${newFileName}{enter}`)

	cy.wait('@moveFile')
}

export const navigateToFolder = (dirPath: string) => {
	const directories = dirPath.split('/')
	directories.forEach((directory) => {
		getRowForFile(directory).should('be.visible').find('[data-cy-files-list-row-name-link]').click()
	})

}

export const closeSidebar = () => {
	// {force: true} as it might be hidden behind toasts
	cy.get('[data-cy-sidebar] .app-sidebar__close').click({ force: true })
}

export const clickOnBreadcrumbs = (label: string) => {
	cy.intercept('PROPFIND', /\/remote.php\/dav\//).as('propfind')
	cy.get('[data-cy-files-content-breadcrumbs]').contains(label).click()
	cy.wait('@propfind')
}

/**
 * Check validity of an input element
 * @param validity The expected validity message (empty string means it is valid)
 * @example
 * ```js
 * cy.findByRole('textbox')
 *     .should(haveValidity(/must not be empty/i))
 * ```
 */
export const haveValidity = (validity: string | RegExp) => {
	if (typeof validity === 'string') {
		return (el: JQuery<HTMLElement>) => expect((el.get(0) as HTMLInputElement).validationMessage).to.equal(validity)
	}
	return (el: JQuery<HTMLElement>) => expect((el.get(0) as HTMLInputElement).validationMessage).to.match(validity)
}

export const deleteFileWithRequest = (user: User, path: string) => {
	// Ensure path starts with a slash and has no double slashes
	path = `/${path}`.replace(/\/+/g, '/')

	cy.request('/csrftoken').then(({ body }) => {
		const requestToken = body.token
		cy.request({
			method: 'DELETE',
			url: `${Cypress.env('baseUrl')}/remote.php/dav/files/${user.userId}${path}`,
			auth: {
				user: user.userId,
				password: user.password,
			},
			headers: {
				requestToken,
			},
			retryOnStatusCodeFailure: true,
		})
	})
}

export const triggerFileListAction = (actionId: string) => {
	cy.get(`button[data-cy-files-list-action="${CSS.escape(actionId)}"]`).last()
		.should('exist').click({ force: true })
}

export const reloadCurrentFolder = () => {
	cy.intercept('PROPFIND', /\/remote.php\/dav\//).as('propfind')
	cy.get('[data-cy-files-content-breadcrumbs]').findByRole('button', { description: 'Reload current directory' }).click()
	cy.wait('@propfind')
}

/**
 * Enable the grid mode for the files list.
 * Will fail if already enabled!
 */
export function enableGridMode() {
	cy.intercept('**/apps/files/api/v1/config/grid_view').as('setGridMode')
	cy.findByRole('button', { name: 'Switch to grid view' })
		.should('be.visible')
		.click()
	cy.wait('@setGridMode')
}

/**
 * Calculate the needed viewport height to limit the visible rows of the file list.
 * Requires a logged in user.
 *
 * @param rows The number of rows that should be displayed at the same time
 */
export function calculateViewportHeight(rows: number): Cypress.Chainable<number> {
	cy.visit('/apps/files')

	return cy.get('[data-cy-files-list]')
		.should('be.visible')
		.then((filesList) => {
			const windowHeight = Cypress.$('body').outerHeight()!
			// Size of other page elements
			const outerHeight = Math.ceil(windowHeight - filesList.outerHeight()!)
			// Size of before and filters
			const beforeHeight = Math.ceil(Cypress.$('.files-list__before').outerHeight()!)
			const filterHeight = Math.ceil(Cypress.$('.files-list__filters').outerHeight()!)
			// Size of the table header
			const tableHeaderHeight = Math.ceil(Cypress.$('[data-cy-files-list-thead]').outerHeight()!)
			// table row height
			const rowHeight = Math.ceil(Cypress.$('[data-cy-files-list-tbody] tr').outerHeight()!)

			// sum it up
			const viewportHeight = outerHeight + beforeHeight + filterHeight + tableHeaderHeight + rows * rowHeight
			cy.log(`Calculated viewport height: ${viewportHeight} (${outerHeight} + ${beforeHeight} + ${filterHeight} + ${tableHeaderHeight} + ${rows} * ${rowHeight})`)
			return cy.wrap(viewportHeight)
		})
}
