import { getRowForFile } from './FilesUtils.ts'

describe('files: Drag and Drop', { testIsolation: true }, () => {
	beforeEach(() => {
		cy.createRandomUser().then((user) => {
			cy.login(user)
		})
		cy.visit('/apps/files')
	})

	it('can drop a file', () => {
		const dataTransfer = new DataTransfer()
		dataTransfer.items.add(new File([], 'single-file.txt'))

		cy.intercept('PUT', /\/remote.php\/dav\/files\//).as('uploadFile')

		cy.get('[data-cy-files-drag-drop-area]').should('not.be.visible')
		// Trigger the drop notice
		cy.get('main.app-content').trigger('dragover', { dataTransfer })
		cy.get('[data-cy-files-drag-drop-area]').should('be.visible')

		// Upload drop a file
		cy.get('[data-cy-files-drag-drop-area]').selectFile({
			fileName: 'single-file.txt',
			contents: ['hello '.repeat(1024)],
		}, { action: 'drag-drop' })

		cy.wait('@uploadFile')

		getRowForFile('single-file.txt').should('be.visible')
		getRowForFile('single-file.txt').find('[data-cy-files-list-row-size]').should('contain', '6 KB')
	})

	it('can drop multiple files', () => {
		const dataTransfer = new DataTransfer()
		dataTransfer.items.add(new File([], 'first.txt'))
		dataTransfer.items.add(new File([], 'second.txt'))

		cy.intercept('PUT', /\/remote.php\/dav\/files\//).as('uploadFile')

		// Trigger the drop notice
		cy.get('main.app-content').trigger('dragover', { dataTransfer })
		cy.get('[data-cy-files-drag-drop-area]').should('be.visible')

		// Upload drop a file
		cy.get('[data-cy-files-drag-drop-area]').selectFile([
			{
				fileName: 'first.txt',
				contents: ['Hello'],
			},
			{
				fileName: 'second.txt',
				contents: ['World'],
			},
		], { action: 'drag-drop' })

		cy.wait('@uploadFile')

		getRowForFile('first.txt').should('be.visible')
		getRowForFile('second.txt').should('be.visible')
	})
})
