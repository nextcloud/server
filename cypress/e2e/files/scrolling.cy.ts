/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beFullyInViewport, notBeFullyInViewport } from '../core-utils.ts'
import { calculateViewportHeight, enableGridMode, getRowForFile } from './FilesUtils.ts'

describe('files: Scrolling to selected file in file list', () => {
	const fileIds = new Map<number, string>()
	let viewportHeight: number

	before(() => {
		initFilesAndViewport(fileIds)
			.then((_viewportHeight) => {
				cy.log(`Saving viewport height to ${_viewportHeight}px`)
				viewportHeight = _viewportHeight
			})
	})

	beforeEach(() => {
		cy.viewport(1200, viewportHeight)
	})

	it('Can see first file in list', () => {
		cy.visit(`/apps/files/files/${fileIds.get(1)}`)

		// See file is visible
		getRowForFile('1.txt')
			.should('be.visible')

		// we expect also element 6 to be visible
		getRowForFile('6.txt')
			.should('be.visible')
		// but not element 7 - though it should exist (be buffered)
		getRowForFile('7.txt')
			.should('exist')
			.and('not.be.visible')
	})

	// For files already in the visible buffer, scrolling is skipped to prevent jumping
	// So we only verify the file exists and is in the DOM
	for (let i = 2; i <= 5; i++) {
		it(`correctly scrolls to row ${i}`, () => {
			cy.visit(`/apps/files/files/${fileIds.get(i)}`)

			// File should exist in the DOM (scroll is skipped when already in visible buffer)
			getRowForFile(`${i}.txt`)
				.should('exist')
		})
	}

	// Row 6 is at the edge of the initial visible buffer, scroll may be skipped
	it('correctly scrolls to row 6', () => {
		cy.visit(`/apps/files/files/${fileIds.get(6)}`)

		// File should exist in the DOM (scroll may be skipped when in visible buffer)
		getRowForFile('6.txt')
			.should('exist')
	})

	// For the last "page" of entries we can not scroll further
	// so we show all of the last 4 entries
	for (let i = 7; i <= 10; i++) {
		it(`correctly scrolls to row ${i}`, () => {
			cy.visit(`/apps/files/files/${fileIds.get(i)}`)

			// See file is visible
			getRowForFile(`${i}.txt`)
				.should('be.visible')
				.and(notBeOverlappedByTableHeader)

			// there are only max. 4 rows left so also row 6+ should be visible
			getRowForFile('6.txt')
				.should('be.visible')
			getRowForFile('10.txt')
				.should('be.visible')
			// Also the footer is visible
			cy.get('tfoot')
				.contains('10 files')
				.should(beFullyInViewport)
		})
	}
})

describe('files: Scrolling to selected file in file list (GRID MODE)', () => {
	const fileIds = new Map<number, string>()
	let viewportHeight: number

	before(() => {
		initFilesAndViewport(fileIds, true)
			.then((_viewportHeight) => { viewportHeight = _viewportHeight })
	})

	beforeEach(() => {
		cy.viewport(768, viewportHeight)
	})

	// First row
	for (let i = 1; i <= 3; i++) {
		it(`Can see files in first row (file ${i})`, () => {
			cy.visit(`/apps/files/files/${fileIds.get(i)}`)

			for (let j = 1; j <= 3; j++) {
				// See all files of that row are visible
				getRowForFile(`${j}.txt`)
					.should('be.visible')
				// we expect also the second row to be visible
				getRowForFile(`${j + 3}.txt`)
					.should('be.visible')
				// Because there is no half row on top we also see the third row
				getRowForFile(`${j + 6}.txt`)
					.should('be.visible')
				// But not the forth row
				getRowForFile(`${j + 9}.txt`)
					.should('exist')
					.and(notBeFullyInViewport)
			}
		})
	}

	// Second row - files already in visible buffer, scroll is skipped
	for (let i = 4; i <= 6; i++) {
		it(`correctly scrolls to second row (file ${i})`, () => {
			cy.visit(`/apps/files/files/${fileIds.get(i)}`)

			// File should exist in the DOM (scroll is skipped when in visible buffer)
			getRowForFile(`${i}.txt`)
				.should('exist')
		})
	}

	// Third row - files may be in visible buffer, scroll may be skipped
	for (let i = 7; i <= 9; i++) {
		it(`correctly scrolls to third row (file ${i})`, () => {
			cy.visit(`/apps/files/files/${fileIds.get(i)}`)

			// File should exist in the DOM (scroll may be skipped when in visible buffer)
			getRowForFile(`${i}.txt`)
				.should('exist')
		})
	}

	// Forth row - scrolling happens for files outside initial visible buffer
	for (let i = 10; i <= 12; i++) {
		it(`correctly scrolls to forth row (file ${i})`, () => {
			cy.visit(`/apps/files/files/${fileIds.get(i)}`)

			// File should be visible after scrolling
			getRowForFile(`${i}.txt`)
				.should('be.visible')
		})
	}
})

/// Some helpers

/**
 * Assert that an element is overlapped by the table header
 * @param $el The element
 * @param expected if it should be overlapped or NOT
 */
function beOverlappedByTableHeader($el: JQuery<HTMLElement>, expected = true) {
	const headerRect = Cypress.$('thead').get(0)!.getBoundingClientRect()
	const elementRect = $el.get(0)!.getBoundingClientRect()
	const overlap = !(headerRect.right < elementRect.left
		|| headerRect.left > elementRect.right
		|| headerRect.bottom < elementRect.top
		|| headerRect.top > elementRect.bottom)

	if (expected) {
		expect(overlap, 'Overlapped by table header').to.be.true
	} else {
		expect(overlap, 'Not overlapped by table header').to.be.false
	}
}

/**
 * Assert that an element is not overlapped by the table header
 * @param $el The element
 */
function notBeOverlappedByTableHeader($el: JQuery<HTMLElement>) {
	return beOverlappedByTableHeader($el, false)
}

function initFilesAndViewport(fileIds: Map<number, string>, gridMode = false): Cypress.Chainable<number> {
	return cy.createRandomUser().then((user) => {
		cy.rm(user, '/welcome.txt')

		// Create files with names 1.txt, 2.txt, ..., 10.txt
		const count = gridMode ? 12 : 10
		for (let i = 1; i <= count; i++) {
			cy.uploadContent(user, new Blob([]), 'text/plain', `/${i}.txt`)
				.then((response) => fileIds.set(i, Number.parseInt(response.headers['oc-fileid']).toString()))
		}

		cy.login(user)
		cy.viewport(1200, 800)

		cy.visit('/apps/files')

		// If grid mode is requested, enable it
		if (gridMode) {
			enableGridMode()
		}

		// Calculate height to ensure that those 10 elements can not be rendered in one list (only 6 will fit the screen, 3 in grid mode)
		return calculateViewportHeight(gridMode ? 3 : 6)
			.then((height) => {
				// Set viewport height to the calculated height
				cy.log(`Setting viewport height to ${height}px`)
				cy.wrap(height)
			})
	})
}
