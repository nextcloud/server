/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

export const closeSidebar = () => {
	// {force: true} as it might be hidden behind toasts
	cy.get('[data-cy-sidebar] .app-sidebar__close').click({ force: true })
}

export const clickOnBreadcrumbs = (label: string) => {
	cy.intercept('PROPFIND', /\/remote.php\/dav\//).as('propfind')
	cy.get('[data-cy-files-content-breadcrumbs]').contains(label).click()
	cy.wait('@propfind')
}
