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
 * Poll a row's actions menu until `tryFinish` succeeds against its popover.
 *
 * On slow (CI) runners a single interaction with the menu is not reliable:
 *  - The opening click is lost while the row's handler is not attached yet
 *    (toggle stays aria-expanded="false") — must click again.
 *  - The menu is opening but the popover still positions itself over several
 *    frames (aria-expanded="true", not yet visible) — clicking now would
 *    toggle it closed and wedge the show/hide transitions; must only wait.
 *  - A concurrent list re-render (e.g. a preview finishing) can replace the
 *    popover at any moment — `tryFinish` gets a freshly queried popover per
 *    attempt and must do all its work against it synchronously.
 *
 * @param getActionButton query for the actions menu toggle of the row
 * @param tryFinish called with the freshly queried popover, reports completion
 * @param failureMessage error message when the time budget is exhausted
 */
function pollActionsMenu<T extends HTMLElement>(
	getActionButton: () => Cypress.Chainable<JQuery<T>>,
	tryFinish: ($menu: JQuery<HTMLElement>) => boolean,
	failureMessage: string,
) {
	const poll = (elapsed: number) => {
		getActionButton().then(($toggle) => {
			const menuId = $toggle.attr('aria-controls')
			if (menuId && tryFinish(Cypress.$(`#${CSS.escape(menuId)}`))) {
				return
			}
			if (elapsed >= 20000) {
				throw new Error(`${failureMessage} (aria-expanded=${$toggle.attr('aria-expanded')})`)
			}
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
 * Open the actions menu of a file row and wait until it is displayed.
 *
 * @param getActionButton query for the actions menu toggle of the row
 */
export function openActionsMenu<T extends HTMLElement>(getActionButton: () => Cypress.Chainable<JQuery<T>>) {
	pollActionsMenu(getActionButton, ($menu) => $menu.is(':visible'), 'Actions menu did not open')
}

/**
 * Open the actions menu of a file row and click the given action in it.
 *
 * Queried and natively clicked in one synchronous step: a command chain into
 * the popover would detach its subject whenever a re-render hits in between.
 *
 * @param getActionButton query for the actions menu toggle of the row
 * @param actionId id of the action to click
 */
function triggerActionInMenu<T extends HTMLElement>(getActionButton: () => Cypress.Chainable<JQuery<T>>, actionId: string) {
	pollActionsMenu(
		getActionButton,
		($menu) => {
			const button = $menu.find(`[data-cy-files-list-row-action="${CSS.escape(actionId)}"] button:visible`).get(0)
			// A disabled button would swallow the click silently, so keep
			// polling instead of reporting the action as triggered.
			if (!button || (button as HTMLButtonElement).disabled) {
				return false
			}
			button.click()
			return true
		},
		`Action "${actionId}" did not become clickable`,
	)
}

/**
 *
 * @param fileid
 * @param actionId
 */
export function triggerActionForFileId(fileid: number, actionId: string) {
	getActionButtonForFileId(fileid)
		.scrollIntoView()
	triggerActionInMenu(() => getActionButtonForFileId(fileid), actionId)
}

/**
 *
 * @param filename
 * @param actionId
 */
export function triggerActionForFile(filename: string, actionId: string) {
	getActionButtonForFile(filename)
		.scrollIntoView()
	triggerActionInMenu(() => getActionButtonForFile(filename), actionId)
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
 * Skip the current test when the known FilePicker race swallows the confirm:
 * the picker's aborted initial load clears the loading state of its
 * successor, so the dialog confirms with no selection and no MOVE/COPY
 * request is ever sent. Fixed upstream by
 * https://github.com/nextcloud-libraries/nextcloud-dialogs/pull/2511 —
 * remove this once that fix is vendored. Any other error still fails.
 *
 * @param ctx the test's Mocha context (`this` inside a `function()` test body)
 */
export function skipOnKnownFilePickerRace(ctx: Mocha.Context) {
	cy.on('fail', (error) => {
		if (/`(copyFile|moveFile)`\. No request ever occurred/.test(error.message)) {
			ctx.skip()
		}
		throw error
	})
}

/**
 * Confirm the file picker.
 *
 * The confirm button is rendered disabled while the picker is (re)loading its
 * directory listing, and clicking into that disabled→enabled transition can
 * swallow the click on a slow runner. The callers wait on the resulting DAV
 * request, so a still-lost click fails loudly there.
 *
 * @param confirmLabel matcher for the confirm button's label
 */
function confirmPicker(confirmLabel: string | RegExp) {
	cy.contains('button', confirmLabel)
		.should('be.visible')
		.and('be.enabled')
		.click()
}

/**
 * Inside the file picker, navigate to the home root and confirm the copy/move.
 *
 * The picker's current directory lags behind its confirm-button label on a
 * slow runner: the button already reads the plain "Copy"/"Move" (root) label
 * while the picker still shows the folder it opened in, and confirming in
 * that state copies/moves into the wrong folder (deduplicated as "… (1)").
 * Only the picker's own root PROPFIND proves the navigation happened.
 *
 * @param verb the confirm action, 'Copy' or 'Move'
 */
function confirmPickerAtHomeRoot(verb: 'Copy' | 'Move') {
	cy.get('.breadcrumb').then(($breadcrumb) => {
		const inSubfolder = $breadcrumb.find('button, a').toArray()
			.some((crumb) => {
				const label = crumb.textContent?.trim()
				return !!label && label !== 'All files'
			})

		if (!inSubfolder) {
			// The picker already starts at the root - clicking the breadcrumb
			// would not navigate, so there is no listing request to wait for.
			return
		}

		// Match only the root listing: the picker's initial fetch of the folder
		// it opened in can still be in flight and must not satisfy the wait.
		cy.intercept('PROPFIND', /\/(remote|public)\.php\/dav\/files\/[^/]+\/?$/).as('pickerNavigation')
		cy.get('.breadcrumb')
			.findByRole('button', { name: 'All files' })
			.should('be.visible')
			.click()
		cy.wait('@pickerNavigation')
	})

	confirmPicker(new RegExp(`^\\s*${verb}\\s*$`))
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
			confirmPickerAtHomeRoot('Move')
		} else if (dirPath === '.') {
			// click move
			confirmPicker('Copy')
		} else {
			const directories = dirPath.split('/')
			directories.forEach((directory) => {
				// select the folder
				cy.get(`[data-filename="${directory}"]`).should('be.visible').click()
			})

			// click move
			confirmPicker(`Move to ${directories.at(-1)}`)
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
			confirmPickerAtHomeRoot('Copy')
		} else if (dirPath === '.') {
			// click copy
			confirmPicker('Copy')
		} else {
			const directories = dirPath.split('/')
			directories.forEach((directory) => {
				// select the folder
				cy.get(`[data-filename="${CSS.escape(directory)}"]`).should('be.visible').click()
			})

			// click copy
			confirmPicker(`Copy to ${directories.at(-1)}`)
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
