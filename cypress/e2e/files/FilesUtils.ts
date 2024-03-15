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

export const getRowForFileId = (fileid: number) => cy.get(`[data-cy-files-list-row-fileid="${fileid}"]`)
export const getRowForFile = (filename: string) => cy.get(`[data-cy-files-list-row-name="${CSS.escape(filename)}"]`)

export const getActionsForFileId = (fileid: number) => getRowForFileId(fileid).find('[data-cy-files-list-row-actions]')
export const getActionsForFile = (filename: string) => getRowForFile(filename).find('[data-cy-files-list-row-actions]')

export const getActionButtonForFileId = (fileid: number) => getActionsForFileId(fileid).find('button[aria-label="Actions"]')
export const getActionButtonForFile = (filename: string) => getActionsForFile(filename).find('button[aria-label="Actions"]')

export const triggerActionForFileId = (fileid: number, actionId: string) => {
	getActionButtonForFileId(fileid).click()
	cy.get(`[data-cy-files-list-row-action="${CSS.escape(actionId)}"] > button`).should('exist').click()
}
export const triggerActionForFile = (filename: string, actionId: string) => {
	getActionButtonForFile(filename).click()
	cy.get(`[data-cy-files-list-row-action="${CSS.escape(actionId)}"] > button`).should('exist').click()
}

export const triggerInlineActionForFileId = (fileid: number, actionId: string) => {
	getActionsForFileId(fileid).find(`button[data-cy-files-list-row-action="${CSS.escape(actionId)}"]`).should('exist').click()
}
export const triggerInlineActionForFile = (filename: string, actionId: string) => {
	getActionsForFile(filename).get(`button[data-cy-files-list-row-action="${CSS.escape(actionId)}"]`).should('exist').click()
}

export const moveFile = (fileName: string, dirName: string) => {
	getRowForFile(fileName).should('be.visible')
	triggerActionForFile(fileName, 'move-copy')

	cy.get('.file-picker').within(() => {
		// intercept the copy so we can wait for it
		cy.intercept('MOVE', /\/remote.php\/dav\/files\//).as('moveFile')

		if (dirName === '/') {
			// select home folder
			cy.get('button[title="Home"]').should('be.visible').click()
			// click move
			cy.contains('button', 'Move').should('be.visible').click()
		} else if (dirName === '.') {
			// click move
			cy.contains('button', 'Copy').should('be.visible').click()
		} else {
			// select the folder
			cy.get(`[data-filename="${dirName}"]`).should('be.visible').click()
			// click move
			cy.contains('button', `Move to ${dirName}`).should('be.visible').click()
		}

		cy.wait('@moveFile')
	})
}

export const copyFile = (fileName: string, dirName: string) => {
	getRowForFile(fileName).should('be.visible')
	triggerActionForFile(fileName, 'move-copy')

	cy.get('.file-picker').within(() => {
		// intercept the copy so we can wait for it
		cy.intercept('COPY', /\/remote.php\/dav\/files\//).as('copyFile')

		if (dirName === '/') {
			// select home folder
			cy.get('button[title="Home"]').should('be.visible').click()
			// click copy
			cy.contains('button', 'Copy').should('be.visible').click()
		} else if (dirName === '.') {
			// click copy
			cy.contains('button', 'Copy').should('be.visible').click()
		} else {
			// select folder
			cy.get(`[data-filename="${dirName}"]`).should('be.visible').click()
			// click copy
			cy.contains('button', `Copy to ${dirName}`).should('be.visible').click()
		}

		cy.wait('@copyFile')
	})
}

export const renameFile = (fileName: string, newFileName: string) => {
	getRowForFile(fileName)
	triggerActionForFile(fileName, 'rename')

	// intercept the move so we can wait for it
	cy.intercept('MOVE', /\/remote.php\/dav\/files\//).as('moveFile')

	getRowForFile(fileName).find('[data-cy-files-list-row-name] input').clear()
	getRowForFile(fileName).find('[data-cy-files-list-row-name] input').type(`${newFileName}{enter}`)

	cy.wait('@moveFile')
}

export const navigateToFolder = (folderName: string) => {
	getRowForFile(folderName).should('be.visible').find('[data-cy-files-list-row-name-link]').click()
}

export const closeSidebar = () => {
	cy.get('[cy-data-sidebar] .app-sidebar__close').click()
}
