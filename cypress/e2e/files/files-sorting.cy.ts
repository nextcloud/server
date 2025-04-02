/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
describe('Files: Sorting the file list', { testIsolation: true }, () => {
	let currentUser
	beforeEach(() => {
		cy.createRandomUser().then((user) => {
			currentUser = user
			cy.login(user)
		})
	})

	it('Files are sorted by name ascending by default', () => {
		cy.uploadContent(currentUser, new Blob(), 'text/plain', '/1 first.txt')
			.uploadContent(currentUser, new Blob(), 'text/plain', '/z last.txt')
			.uploadContent(currentUser, new Blob(), 'text/plain', '/A.txt')
			.uploadContent(currentUser, new Blob(), 'text/plain', '/Ä.txt')
			.mkdir(currentUser, '/m')
			.mkdir(currentUser, '/4')
		cy.login(currentUser)
		cy.visit('/apps/files')

		cy.get('[data-cy-files-list-row]').each(($row, index) => {
			switch (index) {
			case 0: expect($row.attr('data-cy-files-list-row-name')).to.eq('4')
				break
			case 1: expect($row.attr('data-cy-files-list-row-name')).to.eq('m')
				break
			case 2: expect($row.attr('data-cy-files-list-row-name')).to.eq('1 first.txt')
				break
			case 3: expect($row.attr('data-cy-files-list-row-name')).to.eq('A.txt')
				break
			case 4: expect($row.attr('data-cy-files-list-row-name')).to.eq('Ä.txt')
				break
			case 5: expect($row.attr('data-cy-files-list-row-name')).to.eq('welcome.txt')
				break
			case 6: expect($row.attr('data-cy-files-list-row-name')).to.eq('z last.txt')
				break
			}
		})
	})

	/**
	 * Regression test of https://github.com/nextcloud/server/issues/45829
	 */
	it('Filesnames with numbers are sorted by name ascending by default', () => {
		cy.uploadContent(currentUser, new Blob(), 'text/plain', '/name.txt')
			.uploadContent(currentUser, new Blob(), 'text/plain', '/name_03.txt')
			.uploadContent(currentUser, new Blob(), 'text/plain', '/name_02.txt')
			.uploadContent(currentUser, new Blob(), 'text/plain', '/name_01.txt')
		cy.login(currentUser)
		cy.visit('/apps/files')

		cy.get('[data-cy-files-list-row]').each(($row, index) => {
			switch (index) {
			case 0: expect($row.attr('data-cy-files-list-row-name')).to.eq('name.txt')
				break
			case 1: expect($row.attr('data-cy-files-list-row-name')).to.eq('name_01.txt')
				break
			case 2: expect($row.attr('data-cy-files-list-row-name')).to.eq('name_02.txt')
				break
			case 3: expect($row.attr('data-cy-files-list-row-name')).to.eq('name_03.txt')
				break
			}
		})
	})

	it('Can sort by size', () => {
		cy.uploadContent(currentUser, new Blob(), 'text/plain', '/1 tiny.txt')
			.uploadContent(currentUser, new Blob(['a'.repeat(1024)]), 'text/plain', '/z big.txt')
			.uploadContent(currentUser, new Blob(['a'.repeat(512)]), 'text/plain', '/a medium.txt')
			.mkdir(currentUser, '/folder')
		cy.login(currentUser)
		cy.visit('/apps/files')

		// click sort button
		cy.get('th').contains('button', 'Size').click()
		// sorting is set
		cy.contains('th', 'Size').should('have.attr', 'aria-sort', 'ascending')
		// Files are sorted
		cy.get('[data-cy-files-list-row]').each(($row, index) => {
			switch (index) {
			case 0: expect($row.attr('data-cy-files-list-row-name')).to.eq('folder')
				break
			case 1: expect($row.attr('data-cy-files-list-row-name')).to.eq('1 tiny.txt')
				break
			case 2: expect($row.attr('data-cy-files-list-row-name')).to.eq('welcome.txt')
				break
			case 3: expect($row.attr('data-cy-files-list-row-name')).to.eq('a medium.txt')
				break
			case 4: expect($row.attr('data-cy-files-list-row-name')).to.eq('z big.txt')
				break
			}
		})

		// click sort button
		cy.get('th').contains('button', 'Size').click()
		// sorting is set
		cy.contains('th', 'Size').should('have.attr', 'aria-sort', 'descending')
		// Files are sorted
		cy.get('[data-cy-files-list-row]').each(($row, index) => {
			switch (index) {
			case 0: expect($row.attr('data-cy-files-list-row-name')).to.eq('folder')
				break
			case 1: expect($row.attr('data-cy-files-list-row-name')).to.eq('z big.txt')
				break
			case 2: expect($row.attr('data-cy-files-list-row-name')).to.eq('a medium.txt')
				break
			case 3: expect($row.attr('data-cy-files-list-row-name')).to.eq('welcome.txt')
				break
			case 4: expect($row.attr('data-cy-files-list-row-name')).to.eq('1 tiny.txt')
				break
			}
		})
	})

	it('Can sort by mtime', () => {
		cy.uploadContent(currentUser, new Blob(), 'text/plain', '/1.txt', Date.now() / 1000 - 86400 - 1000)
			.uploadContent(currentUser, new Blob(['a'.repeat(1024)]), 'text/plain', '/z.txt', Date.now() / 1000 - 86400)
			.uploadContent(currentUser, new Blob(['a'.repeat(512)]), 'text/plain', '/a.txt', Date.now() / 1000 - 86400 - 500)
		cy.login(currentUser)
		cy.visit('/apps/files')

		// click sort button
		cy.get('th').contains('button', 'Modified').click()
		// sorting is set
		cy.contains('th', 'Modified').should('have.attr', 'aria-sort', 'ascending')
		// Files are sorted
		cy.get('[data-cy-files-list-row]').each(($row, index) => {
			switch (index) {
			case 0: expect($row.attr('data-cy-files-list-row-name')).to.eq('welcome.txt') // uploaded right now
				break
			case 1: expect($row.attr('data-cy-files-list-row-name')).to.eq('z.txt') // fake time of yesterday
				break
			case 2: expect($row.attr('data-cy-files-list-row-name')).to.eq('a.txt') // fake time of yesterday and few minutes
				break
			case 3: expect($row.attr('data-cy-files-list-row-name')).to.eq('1.txt') // fake time of yesterday and ~15 minutes ago
				break
			}
		})

		// reverse order
		cy.get('th').contains('button', 'Modified').click()
		cy.contains('th', 'Modified').should('have.attr', 'aria-sort', 'descending')
		cy.get('[data-cy-files-list-row]').each(($row, index) => {
			switch (index) {
			case 3: expect($row.attr('data-cy-files-list-row-name')).to.eq('welcome.txt') // uploaded right now
				break
			case 2: expect($row.attr('data-cy-files-list-row-name')).to.eq('z.txt') // fake time of yesterday
				break
			case 1: expect($row.attr('data-cy-files-list-row-name')).to.eq('a.txt') // fake time of yesterday and few minutes
				break
			case 0: expect($row.attr('data-cy-files-list-row-name')).to.eq('1.txt') // fake time of yesterday and ~15 minutes ago
				break
			}
		})
	})

	it('Favorites are sorted first', () => {
		cy.uploadContent(currentUser, new Blob(), 'text/plain', '/1.txt', Date.now() / 1000 - 86400 - 1000)
			.uploadContent(currentUser, new Blob(['a'.repeat(1024)]), 'text/plain', '/z.txt', Date.now() / 1000 - 86400)
			.uploadContent(currentUser, new Blob(['a'.repeat(512)]), 'text/plain', '/a.txt', Date.now() / 1000 - 86400 - 500)
			.setFileAsFavorite(currentUser, '/a.txt')
		cy.login(currentUser)
		cy.visit('/apps/files')

		cy.log('By name - ascending')
		cy.get('th').contains('button', 'Name').click()
		cy.contains('th', 'Name').should('have.attr', 'aria-sort', 'ascending')

		cy.get('[data-cy-files-list-row]').each(($row, index) => {
			switch (index) {
			case 0: expect($row.attr('data-cy-files-list-row-name')).to.eq('a.txt')
				break
			case 1: expect($row.attr('data-cy-files-list-row-name')).to.eq('1.txt')
				break
			case 2: expect($row.attr('data-cy-files-list-row-name')).to.eq('welcome.txt')
				break
			case 3: expect($row.attr('data-cy-files-list-row-name')).to.eq('z.txt')
				break
			}
		})

		cy.log('By name - descending')
		cy.get('th').contains('button', 'Name').click()
		cy.contains('th', 'Name').should('have.attr', 'aria-sort', 'descending')

		cy.get('[data-cy-files-list-row]').each(($row, index) => {
			switch (index) {
			case 0: expect($row.attr('data-cy-files-list-row-name')).to.eq('a.txt')
				break
			case 3: expect($row.attr('data-cy-files-list-row-name')).to.eq('1.txt')
				break
			case 2: expect($row.attr('data-cy-files-list-row-name')).to.eq('welcome.txt')
				break
			case 1: expect($row.attr('data-cy-files-list-row-name')).to.eq('z.txt')
				break
			}
		})

		cy.log('By size - ascending')
		cy.get('th').contains('button', 'Size').click()
		cy.contains('th', 'Size').should('have.attr', 'aria-sort', 'ascending')

		cy.get('[data-cy-files-list-row]').each(($row, index) => {
			switch (index) {
			case 0: expect($row.attr('data-cy-files-list-row-name')).to.eq('a.txt')
				break
			case 1: expect($row.attr('data-cy-files-list-row-name')).to.eq('1.txt')
				break
			case 2: expect($row.attr('data-cy-files-list-row-name')).to.eq('welcome.txt')
				break
			case 3: expect($row.attr('data-cy-files-list-row-name')).to.eq('z.txt')
				break
			}
		})

		cy.log('By size - descending')
		cy.get('th').contains('button', 'Size').click()
		cy.contains('th', 'Size').should('have.attr', 'aria-sort', 'descending')

		cy.get('[data-cy-files-list-row]').each(($row, index) => {
			switch (index) {
			case 0: expect($row.attr('data-cy-files-list-row-name')).to.eq('a.txt')
				break
			case 3: expect($row.attr('data-cy-files-list-row-name')).to.eq('1.txt')
				break
			case 2: expect($row.attr('data-cy-files-list-row-name')).to.eq('welcome.txt')
				break
			case 1: expect($row.attr('data-cy-files-list-row-name')).to.eq('z.txt')
				break
			}
		})

		cy.log('By mtime - ascending')
		cy.get('th').contains('button', 'Modified').click()
		cy.contains('th', 'Modified').should('have.attr', 'aria-sort', 'ascending')

		cy.get('[data-cy-files-list-row]').each(($row, index) => {
			switch (index) {
			case 0: expect($row.attr('data-cy-files-list-row-name')).to.eq('a.txt')
				break
			case 1: expect($row.attr('data-cy-files-list-row-name')).to.eq('welcome.txt')
				break
			case 2: expect($row.attr('data-cy-files-list-row-name')).to.eq('z.txt')
				break
			case 3: expect($row.attr('data-cy-files-list-row-name')).to.eq('1.txt')
				break
			}
		})

		cy.log('By mtime - descending')
		cy.get('th').contains('button', 'Modified').click()
		cy.contains('th', 'Modified').should('have.attr', 'aria-sort', 'descending')

		cy.get('[data-cy-files-list-row]').each(($row, index) => {
			switch (index) {
			case 0: expect($row.attr('data-cy-files-list-row-name')).to.eq('a.txt')
				break
			case 1: expect($row.attr('data-cy-files-list-row-name')).to.eq('1.txt')
				break
			case 2: expect($row.attr('data-cy-files-list-row-name')).to.eq('z.txt')
				break
			case 3: expect($row.attr('data-cy-files-list-row-name')).to.eq('welcome.txt')
				break
			}
		})
	})

	it('Sorting works after switching view twice', () => {
		cy.uploadContent(currentUser, new Blob(), 'text/plain', '/1 tiny.txt')
			.uploadContent(currentUser, new Blob(['a'.repeat(1024)]), 'text/plain', '/z big.txt')
			.uploadContent(currentUser, new Blob(['a'.repeat(512)]), 'text/plain', '/a medium.txt')
			.mkdir(currentUser, '/folder')
		cy.login(currentUser)
		cy.visit('/apps/files')

		// click sort button twice
		cy.get('th').contains('button', 'Size').click()
		cy.get('th').contains('button', 'Size').click()

		// switch to personal and click sort button twice again
		cy.get('[data-cy-files-navigation-item="personal"]').click()
		cy.get('th').contains('button', 'Size').click()
		cy.get('th').contains('button', 'Size').click()

		// switch back to files view and do actual assertions
		cy.get('[data-cy-files-navigation-item="files"]').click()

		// click sort button
		cy.get('th').contains('button', 'Size').click()
		// sorting is set
		cy.contains('th', 'Size').should('have.attr', 'aria-sort', 'ascending')
		// Files are sorted
		cy.get('[data-cy-files-list-row]').each(($row, index) => {
			switch (index) {
			case 0: expect($row.attr('data-cy-files-list-row-name')).to.eq('folder')
				break
			case 1: expect($row.attr('data-cy-files-list-row-name')).to.eq('1 tiny.txt')
				break
			case 2: expect($row.attr('data-cy-files-list-row-name')).to.eq('welcome.txt')
				break
			case 3: expect($row.attr('data-cy-files-list-row-name')).to.eq('a medium.txt')
				break
			case 4: expect($row.attr('data-cy-files-list-row-name')).to.eq('z big.txt')
				break
			}
		})

		// click sort button
		cy.get('th').contains('button', 'Size').click()
		// sorting is set
		cy.contains('th', 'Size').should('have.attr', 'aria-sort', 'descending')
		// Files are sorted
		cy.get('[data-cy-files-list-row]').each(($row, index) => {
			switch (index) {
			case 0: expect($row.attr('data-cy-files-list-row-name')).to.eq('folder')
				break
			case 1: expect($row.attr('data-cy-files-list-row-name')).to.eq('z big.txt')
				break
			case 2: expect($row.attr('data-cy-files-list-row-name')).to.eq('a medium.txt')
				break
			case 3: expect($row.attr('data-cy-files-list-row-name')).to.eq('welcome.txt')
				break
			case 4: expect($row.attr('data-cy-files-list-row-name')).to.eq('1 tiny.txt')
				break
			}
		})
	})
})
