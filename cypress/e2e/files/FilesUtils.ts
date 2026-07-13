/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

const ACTION_COPY_MOVE = 'move-copy'

export const getRowForFileId = (fileid: string | number) => cy.get(`[data-cy-files-list-row-fileid="${fileid}"]`)
export const getRowForFile = (filename: string) => cy.get(`[data-cy-files-list-row-name="${CSS.escape(filename)}"]`)

// Atomic query so the lookup is retried as a whole when rows re-render
// (chained .find() can fail with "subject no longer attached" mid-render).
export const getActionsForFileId = (fileid: number) => cy.get(`[data-cy-files-list-row-fileid="${fileid}"] [data-cy-files-list-row-actions]`)
export const getActionsForFile = (filename: string) => cy.get(`[data-cy-files-list-row-name="${CSS.escape(filename)}"] [data-cy-files-list-row-actions]`)

export const getActionButtonForFileId = (fileid: number) => getActionsForFileId(fileid).findByRole('button', { name: 'Actions' })
export const getActionButtonForFile = (filename: string) => getActionsForFile(filename).findByRole('button', { name: 'Actions' })

/**
 *
 * @param fileid
 * @param actionId
 */
export function getActionEntryForFileId(fileid: number, actionId: string) {
	return getActionButtonForFileId(fileid)
		.should('have.attr', 'aria-controls')
		.then((menuId) => cy.get(`#${menuId}`)
			.should('exist')
			.find(`[data-cy-files-list-row-action="${CSS.escape(actionId)}"]`))
}

/**
 *
 * @param file
 * @param actionId
 */
export function getActionEntryForFile(file: string, actionId: string) {
	return getActionButtonForFile(file)
		.should('have.attr', 'aria-controls')
		.then((menuId) => cy.get(`#${menuId}`)
			.should('exist')
			.find(`[data-cy-files-list-row-action="${CSS.escape(actionId)}"]`))
}

/**
 *
 * @param fileid
 * @param actionId
 */
export function getInlineActionEntryForFileId(fileid: number, actionId: string) {
	return cy.get(`[data-cy-files-list-row-fileid="${fileid}"] [data-cy-files-list-row-action="${CSS.escape(actionId)}"]`)
}

/**
 *
 * @param file
 * @param actionId
 */
export function getInlineActionEntryForFile(file: string, actionId: string) {
	return cy.get(`[data-cy-files-list-row-name="${CSS.escape(file)}"] [data-cy-files-list-row-action="${CSS.escape(actionId)}"]`)
}

/**
 * Open the actions menu of a file row and wait until it is displayed.
 *
 * Click exactly once, then wait: while the popover opens, the toggle
 * already reports aria-expanded="true" although the menu is still hidden —
 * the popover positions itself over several frames, which can take seconds
 * on slow (CI) runners. Clicking again in that state toggles the menu
 * closed and tangles the popover's show and hide transitions into a stuck,
 * permanently invisible popover. Clicking is only safe while the toggle
 * reports "closed", e.g. after an auto-close caused by a stray outside
 * click.
 *
 * @param getActionButton query for the actions menu toggle of the row
 */
export function openActionsMenu(getActionButton: () => Cypress.Chainable<JQuery<HTMLElement>>) {
	// The menu open has two failure modes on slow runners, needing opposite
	// responses:
	//   - The click is lost because the row's handler is not attached yet, so
	//     the toggle stays collapsed (aria-expanded="false"). We must click
	//     again.
	//   - The menu is opening but the popover is still positioning over a few
	//     frames (aria-expanded="true", not yet visible). Clicking again here
	//     would toggle it closed and tangle the show/hide transitions, so we
	//     must only wait.
	// Poll accordingly until the menu is actually displayed.
	const poll = (elapsed: number) => {
		getActionButton().then(($toggle) => {
			const menuId = $toggle.attr('aria-controls')
			if (menuId && Cypress.$(`#${menuId}`).is(':visible')) {
				return
			}
			if (elapsed >= 20000) {
				throw new Error(`Actions menu did not open (aria-expanded=${$toggle.attr('aria-expanded')})`)
			}
			// Only (re)open while collapsed; never click a menu that is mid-open.
			if ($toggle.attr('aria-expanded') !== 'true') {
				cy.wrap($toggle).click({ force: true }) // force to avoid issues with overlaying file list header
			}
			// eslint-disable-next-line cypress/no-unnecessary-waiting -- give the popover a moment to open/position before re-checking
			cy.wait(250)
			poll(elapsed + 250)
		})
	}
	poll(0)
}

/**
 *
 * @param fileid
 * @param actionId
 */
export function triggerActionForFileId(fileid: number, actionId: string) {
	getActionButtonForFileId(fileid)
		.scrollIntoView()
	openActionsMenu(() => getActionButtonForFileId(fileid))
	getActionEntryForFileId(fileid, actionId)
		.find('button')
		.should('be.visible')
		.click()
}

/**
 *
 * @param filename
 * @param actionId
 */
export function triggerActionForFile(filename: string, actionId: string) {
	getActionButtonForFile(filename)
		.scrollIntoView()
	openActionsMenu(() => getActionButtonForFile(filename))
	getActionEntryForFile(filename, actionId)
		.find('button')
		.should('be.visible')
		.click()
}

/**
 *
 * @param fileid
 * @param actionId
 */
export function triggerInlineActionForFileId(fileid: number, actionId: string) {
	getActionsForFileId(fileid)
		.find(`button[data-cy-files-list-row-action="${CSS.escape(actionId)}"]`)
		.should('exist')
		.click()
}
/**
 *
 * @param filename
 * @param actionId
 */
export function triggerInlineActionForFile(filename: string, actionId: string) {
	getActionsForFile(filename)
		.find(`button[data-cy-files-list-row-action="${CSS.escape(actionId)}"]`)
		.should('exist')
		.click()
}

/**
 *
 */
export function selectAllFiles() {
	cy.get('[data-cy-files-list-selection-checkbox]')
		.findByRole('checkbox', { checked: false })
		.click({ force: true })
}
/**
 *
 */
export function deselectAllFiles() {
	cy.get('[data-cy-files-list-selection-checkbox]')
		.findByRole('checkbox', { checked: true })
		.click({ force: true })
}

/**
 *
 * @param filename
 * @param options
 */
export function selectRowForFile(filename: string, options: Partial<Cypress.ClickOptions> = {}) {
	getRowForFile(filename)
		.find('[data-cy-files-list-row-checkbox]')
		.findByRole('checkbox')
		// don't use click to avoid triggering side effects events
		.trigger('change', { ...options, force: true })
		.should('be.checked')
	cy.get('[data-cy-files-list-selection-checkbox]').findByRole('checkbox').should('satisfy', (elements) => {
		return elements.length === 1 && (elements[0].checked === true || elements[0].indeterminate === true)
	})
}

export const getSelectionActionButton = () => cy.get('[data-cy-files-list-selection-actions]').findByRole('button', { name: 'Actions' })
export const getSelectionActionEntry = (actionId: string) => cy.get(`[data-cy-files-list-selection-action="${CSS.escape(actionId)}"]`)
/**
 *
 * @param actionId
 */
export function triggerSelectionAction(actionId: string) {
	// Even if it's inline, we open the action menu to get all actions visible
	getSelectionActionButton().click({ force: true })
	// the entry might already be a button or a button might its child
	getSelectionActionEntry(actionId)
		.then(($el) => $el.is('button') ? cy.wrap($el) : cy.wrap($el).findByRole('menuitem').last())
		.should('exist')
		.click()
}

/**
 * Inside the file picker, navigate to the home root and confirm the copy/move.
 *
 * On a slow runner two things race and must be handled deterministically:
 *  - The picker's current directory lags behind its confirm-button label: the
 *    button already reads the plain "Copy"/"Move" (root) label while the picker
 *    still shows the folder it opened in, so clicking it copies/moves into the
 *    wrong folder (deduplicated as "… (1)"). We therefore wait for the picker
 *    to actually reload its listing (its own PROPFIND) after clicking the
 *    "All files" breadcrumb, so its current directory really is the root before
 *    we confirm.
 *  - The confirm click itself can be dropped (its handler is not attached yet),
 *    so no request fires. We re-click until the resulting request is seen.
 *
 * @param verb the confirm action, 'Copy' or 'Move'
 * @param requestAlias the intercept alias for the resulting DAV request
 */
function confirmPickerAtHomeRoot(verb: 'Copy' | 'Move', requestAlias: string) {
	cy.intercept('PROPFIND', /\/(remote|public)\.php\/dav\/files\//).as('pickerNavigation')
	cy.get('.breadcrumb')
		.findByRole('button', { name: 'All files' })
		.should('be.visible')
		.click()
	// Wait for the picker to actually fetch the root listing, so its current
	// directory (the copy/move target) is the root and not the folder it opened
	// in — otherwise the confirm below would target the wrong folder.
	cy.wait('@pickerNavigation')

	// The plain "Copy"/"Move" label only exists once the picker is at the root.
	const confirmLabel = new RegExp(`^\\s*${verb}\\s*$`)
	const clickUntilRequestFires = (attemptsLeft: number) => {
		cy.contains('button', confirmLabel).should('be.visible').click()
		// eslint-disable-next-line cypress/no-unnecessary-waiting -- give a dropped click a moment to be noticed
		cy.wait(2000)
		cy.get(`@${requestAlias}.all`).then((calls) => {
			if (calls.length === 0 && attemptsLeft > 0) {
				clickUntilRequestFires(attemptsLeft - 1)
			}
		})
	}
	clickUntilRequestFires(4)
}

/**
 *
 * @param fileName
 * @param dirPath
 */
export function moveFile(fileName: string, dirPath: string) {
	getRowForFile(fileName).should('be.visible')
	triggerActionForFile(fileName, ACTION_COPY_MOVE)

	cy.get('.file-picker').within(() => {
		// intercept the copy so we can wait for it
		cy.intercept('MOVE', /\/(remote|public)\.php\/dav\/files\//).as('moveFile')

		if (dirPath === '/') {
			confirmPickerAtHomeRoot('Move', 'moveFile')
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

/**
 *
 * @param fileName
 * @param dirPath
 */
export function copyFile(fileName: string, dirPath: string) {
	getRowForFile(fileName).should('be.visible')
	triggerActionForFile(fileName, ACTION_COPY_MOVE)

	cy.get('.file-picker').within(() => {
		// intercept the copy so we can wait for it
		cy.intercept('COPY', /\/(remote|public)\.php\/dav\/files\//).as('copyFile')

		if (dirPath === '/') {
			confirmPickerAtHomeRoot('Copy', 'copyFile')
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

/**
 *
 * @param fileName
 * @param newFileName
 */
export function renameFile(fileName: string, newFileName: string) {
	getRowForFile(fileName)
		.should('exist')
		.scrollIntoView()

	triggerActionForFile(fileName, 'rename')

	// intercept the move so we can wait for it
	cy.intercept('MOVE', /\/(remote|public)\.php\/dav\/files\//).as('moveFile')

	getRowForFile(fileName)
		.find('[data-cy-files-list-row-name] input')
		.type(`{selectAll}${newFileName}{enter}`)

	cy.wait('@moveFile')
}

/**
 *
 * @param dirPath
 */
export function navigateToFolder(dirPath: string) {
	const directories = dirPath.split('/')
	for (const directory of directories) {
		if (directory === '') {
			continue
		}

		getRowForFile(directory).should('be.visible').find('[data-cy-files-list-row-name-link]').click()
	}
}

/**
 * Close the sidebar
 */
export function closeSidebar() {
	// {force: true} as it might be hidden behind toasts
	cy.get('[data-cy-sidebar] .app-sidebar__close')
		.click({ force: true })
	cy.get('[data-cy-sidebar]')
		.should('not.be.visible')
	// eslint-disable-next-line cypress/no-unnecessary-waiting -- wait for the animation to finish
	cy.wait(500)
	cy.url()
		.should('not.contain', 'opendetails')
	// close all toasts
	cy.get('.toast-success')
		.if()
		.findAllByRole('button')
		.click({ force: true, multiple: true })
}

/**
 *
 * @param label
 */
export function clickOnBreadcrumbs(label: string) {
	cy.intercept('PROPFIND', /\/remote.php\/dav\//).as('propfind')
	cy.get('[data-cy-files-content-breadcrumbs]').contains(label).click()
	cy.wait('@propfind')
}

/**
 *
 * @param folderName
 */
export function createFolder(folderName: string) {
	cy.intercept('MKCOL', /\/remote.php\/dav\/files\//).as('createFolder')

	// TODO: replace by proper data-cy selectors
	cy.get('[data-cy-upload-picker] .action-item__menutoggle').first().click()
	cy.get('[data-cy-upload-picker-menu-entry="newFolder"] button').click()
	cy.get('[data-cy-files-new-node-dialog]').should('be.visible')
	cy.get('[data-cy-files-new-node-dialog-input]').type(`{selectall}${folderName}`)
	cy.get('[data-cy-files-new-node-dialog-submit]').click()

	cy.wait('@createFolder')

	getRowForFile(folderName).should('be.visible')
}

/**
 * Check validity of an input element
 *
 * @param validity The expected validity message (empty string means it is valid)
 * @example
 * ```js
 * cy.findByRole('textbox')
 *     .should(haveValidity(/must not be empty/i))
 * ```
 */
export function haveValidity(validity: string | RegExp) {
	if (typeof validity === 'string') {
		return (el: JQuery<HTMLElement>) => expect((el.get(0) as HTMLInputElement).validationMessage).to.equal(validity)
	}
	return (el: JQuery<HTMLElement>) => expect((el.get(0) as HTMLInputElement).validationMessage).to.match(validity)
}

/**
 *
 * @param user
 * @param path
 */
export function deleteFileWithRequest(user: User, path: string) {
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

/**
 *
 * @param actionId
 */
export function triggerFileListAction(actionId: string) {
	cy.get(`button[data-cy-files-list-action="${CSS.escape(actionId)}"]`).last()
		.should('exist').click({ force: true })
}

/**
 * Reloads the current folder
 *
 * @param intercept if true this will wait for the PROPFIND to complete before it resolves
 */
export function reloadCurrentFolder(intercept = true) {
	cy.intercept('PROPFIND', /\/remote.php\/dav\//).as('propfind')
	cy.findByRole('navigation', { name: 'Current directory path' })
		.findAllByRole('button')
		.filter('[aria-haspopup="menu"]')
		.click()
	cy.findByRole('menu')
		.should('be.visible')
		.findByRole('menuitem', { name: 'Reload content' })
		.click()

	if (intercept) {
		cy.wait('@propfind')
	}
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

	cy.get('[data-cy-files-list]')
		.should('be.visible')

	cy.get('[data-cy-files-list-tbody] tr', { timeout: 5000 })
		.and('be.visible')

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
