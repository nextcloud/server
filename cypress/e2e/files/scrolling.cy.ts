/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'
import { getRowForFile } from './FilesUtils'

describe('files: Scrolling to selected file in file list', { testIsolation: true }, () => {
	const fileIds = new Map<number, string>()
	let user: User

	before(() => {
		cy.createRandomUser().then(($user) => {
			user = $user

			cy.rm(user, '/welcome.txt')
			for (let i = 1; i <= 10; i++) {
				cy.uploadContent(user, new Blob([]), 'text/plain', `/${i}.txt`)
					.then((response) => fileIds.set(i, Number.parseInt(response.headers['oc-fileid']).toString()))
			}
		})
	})

	beforeEach(() => {
		// Adjust height to ensure that those 10 elements can not be rendered in one list
		cy.viewport(1200, 6 * 55 /* rows */ + 55 /* table header */ + 50 /* navigation header */ + 50 /* breadcrumbs */ + 46 /* file filters */)
		cy.login(user)
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

	// Same kind of tests for partially visible top and bottom
	for (let i = 2; i <= 5; i++) {
		it(`correctly scrolls to row ${i}`, () => {
			cy.visit(`/apps/files/files/${fileIds.get(i)}`)

			// See file is visible
			getRowForFile(`${i}.txt`)
				.should('be.visible')
				.and(notBeOverlappedByTableHeader)

			// we expect also element +4 to be visible
			// (6 visible rows -> 5 without our scrolled row -> so we only have 4 fully visible others + two 1/2 hidden rows)
			getRowForFile(`${i + 4}.txt`)
				.should('be.visible')
			// but not element -1 or +5 - though it should exist (be buffered)
			getRowForFile(`${i - 1}.txt`)
				.should('exist')
				.and(beOverlappedByTableHeader)
			getRowForFile(`${i + 5}.txt`)
				.should('exist')
				.and('be.visible')
				.and(notBeFullyInViewport)
		})
	}

	// this will have half of the footer visible
	it(`correctly scrolls to row 6`, () => {
		cy.visit(`/apps/files/files/${fileIds.get(6)}`)

		// See file is visible
		getRowForFile(`6.txt`)
			.should('be.visible')
			.and(notBeOverlappedByTableHeader)

		// we expect also element 7,8,9,10 visible
		getRowForFile(`10.txt`)
			.should('be.visible')
		// but not row 5
		getRowForFile(`5.txt`)
			.should('exist')
			.and(beOverlappedByTableHeader)
		// see footer is only shown partly
		cy.get('tfoot')
			.should('exist')
			.and(notBeFullyInViewport)
	})

	// Same kind of tests for partially visible top and bottom
	for (let i = 7; i <= 10; i++) {
		it(`correctly scrolls to row ${i}`, () => {
			cy.visit(`/apps/files/files/${fileIds.get(i)}`)

			// See file is visible
			getRowForFile(`${i}.txt`)
				.should('be.visible')
				.and(notBeOverlappedByTableHeader)

			// there are only max. 3 rows left so also row 6+ should be visible
			getRowForFile(`6.txt`)
				.should('be.visible')
			getRowForFile(`10.txt`)
				.should('be.visible')
			// Also the footer is visible
			cy.get('tfoot')
				.contains('10 files')
				.should('be.visible')
		})
	}
})

describe('files: Scrolling to selected file in file list (GRID MODE)', { testIsolation: true }, () => {
	const fileIds = new Map<number, string>()
	let user: User

	before(() => {
		cy.createRandomUser().then(($user) => {
			user = $user

			cy.rm(user, '/welcome.txt')
			for (let i = 1; i <= 12; i++) {
				cy.uploadContent(user, new Blob([]), 'text/plain', `/${i}.txt`)
					.then((response) => fileIds.set(i, Number.parseInt(response.headers['oc-fileid']).toString()))
			}
			// Set grid mode
			cy.login(user)
			cy.intercept('**/apps/files/api/v1/config/grid_view').as('setGridMode')
			cy.visit('/apps/files')
			cy.findByRole('button', { name: 'Switch to grid view' })
				.should('be.visible')
				.click()
			cy.wait('@setGridMode')
		})
	})

	beforeEach(() => {
		// Adjust height to ensure that those 12 files can not be rendered in one list
		// 768px width will limit the columns to 3
		cy.viewport(768, 3 * 246 /* rows */ + 55 /* table header */ + 50 /* navigation header */ + 50 /* breadcrumbs */ + 46 /* file filters */)
		cy.login(user)
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
				getRowForFile(`${j+3}.txt`)
					.should('be.visible')
				// Because there is no half row on top we also see the third row
				getRowForFile(`${j+6}.txt`)
					.should('be.visible')
				// But not the forth row
				getRowForFile(`${j+9}.txt`)
					.should('exist')
					.and(notBeFullyInViewport)
			}
		})
	}

	// Second row
	// Same kind of tests for partially visible top and bottom
	for (let i = 4; i <= 6; i++) {
		it(`correctly scrolls to second row (file ${i})`, () => {
			cy.visit(`/apps/files/files/${fileIds.get(i)}`)

			// See all three files of that row are visible
			for (let j = 4; j <= 6; j++) {
				getRowForFile(`${j}.txt`)
					.should('be.visible')
					.and(notBeOverlappedByTableHeader)
				// we expect also the next row to be visible
				getRowForFile(`${j + 3}.txt`)
					.should('be.visible')
				// but not the row below (should be half cut)
				getRowForFile(`${j + 6}.txt`)
					.should('exist')
					.and(notBeFullyInViewport)
				// Same for the row above
				getRowForFile(`${j - 3}.txt`)
					.should('exist')
					.and(beOverlappedByTableHeader)
			}
		})
	}

	// Third row
	// this will have half of the footer visible and half of the previous row
	for (let i = 7; i <= 9; i++) {
		it(`correctly scrolls to third row (file ${i})`, () => {
			cy.visit(`/apps/files/files/${fileIds.get(i)}`)

			// See all three files of that row are visible
			for (let j = 7; j <= 9; j++) {
				getRowForFile(`${j}.txt`)
					.should('be.visible')
				// we expect also the next row to be visible
				getRowForFile(`${j + 3}.txt`)
					.should('be.visible')
				// but not the row above
				getRowForFile(`${j - 3}.txt`)
					.should('exist')
					.and(beOverlappedByTableHeader)
			}

			// see footer is only shown partly
			cy.get('tfoot')
				.should('exist')
				.and(notBeFullyInViewport)
		})
	}

	// Forth row which only has row 4 and 3 visible and the full footer
	for (let i = 10; i <= 12; i++) {
		it(`correctly scrolls to forth row (file ${i})`, () => {
			cy.visit(`/apps/files/files/${fileIds.get(i)}`)

			// See all three files of that row are visible
			for (let j = 10; j <= 12; j++) {
				getRowForFile(`${j}.txt`)
					.should('be.visible')
					.and(notBeOverlappedByTableHeader)
				// we expect also the row above to be visible
				getRowForFile(`${j - 3}.txt`)
					.should('be.visible')
			}

			// see footer is shown
			cy.get('tfoot')
				.should('be.visible')
		})
	}
})

/// Some helpers

function notBeOverlappedByTableHeader($el: JQuery<HTMLElement>) {
	return beOverlappedByTableHeader($el, false)
}

function beOverlappedByTableHeader($el: JQuery<HTMLElement>, expected = true) {
	const headerRect = Cypress.$('thead').get(0)!.getBoundingClientRect()
	const elementRect = $el.get(0)!.getBoundingClientRect()
	const overlap = !(headerRect.right < elementRect.left || 
		headerRect.left > elementRect.right || 
		headerRect.bottom < elementRect.top || 
		headerRect.top > elementRect.bottom)

	if (expected) {
		expect(overlap, 'Overlapped by table header').to.be.true
	} else {
		expect(overlap, 'Not overlapped by table header').to.be.false
	}
}

function beFullyInViewport($el: JQuery<HTMLElement>, expected = true) {
	const { top, left, bottom, right } = $el.get(0)!.getBoundingClientRect()
	const { innerHeight, innerWidth } = window
	const fullyVisible = top >= 0 && left >= 0 && bottom <= innerHeight && right <= innerWidth

	if (expected) {
		expect(fullyVisible, 'Fully within viewport').to.be.true
	} else {
		expect(fullyVisible, 'Not fully within viewport').to.be.false
	}
}

function notBeFullyInViewport($el: JQuery<HTMLElement>) {
	return beFullyInViewport($el, false)
}
