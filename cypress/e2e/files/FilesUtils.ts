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

export const getRowForFile = (filename: string) => cy.get(`[data-cy-files-list-row-name="${CSS.escape(filename)}"]`)

export const getActionsForFile = (filename: string) => getRowForFile(filename).find('[data-cy-files-list-row-actions]')

export const getActionButtonForFile = (filename: string) => getActionsForFile(filename).find('button[aria-label="Actions"]')

export const triggerActionForFile = (filename: string, actionId: string) => {
	getActionButtonForFile(filename).click({ force: true })
	cy.get(`[data-cy-files-list-row-action="${CSS.escape(actionId)}"] > button`).should('be.visible').click({ force: true })
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

	getRowForFile(fileName).find('[data-cy-files-list-row-name] input').clear()
	getRowForFile(fileName).find('[data-cy-files-list-row-name] input').type(`${newFileName}{enter}`)

	cy.wait('@moveFile')
}

export const navigateToFolder = (dirPath: string) => {
	const directories = dirPath.split('/')
	directories.forEach((directory) => {
		getRowForFile(directory).should('be.visible').find('[data-cy-files-list-row-name-link]').click()
	})
}
