describe('A11y tests', function() {
	before(function() {
		// Init user
		cy.createRandomUser().then(user => {
			// Upload test files
			cy.uploadFile(user, 'image1.jpg', 'image/jpeg')
			cy.uploadFile(user, 'image2.jpg', 'image/jpeg')
			cy.uploadFile(user, 'video1.mp4', 'video/mp4')

			// Visit nextcloud
			cy.login(user)
			cy.visit('/apps/files')
		})
	})

	after(function() {
		cy.logout()
	})

	it('See files in the list', function() {
		cy.getFile('image1.jpg', { timeout: 10000 })
			.should('contain', 'image1 .jpg')
		cy.getFile('image2.jpg', { timeout: 10000 })
			.should('contain', 'image2 .jpg')
		cy.getFile('video1.mp4', { timeout: 10000 })
			.should('contain', 'video1 .mp4')
	})

	it('Open the viewer on file click', function() {
		cy.openFile('image2.jpg')
		cy.get('body > .viewer').should('be.visible')
	})

	it('Does not see a loading animation', function() {
		cy.get('body > .viewer', { timeout: 10000 })
			.should('be.visible')
			.and('have.class', 'modal-mask')
			.and('not.have.class', 'icon-loading')
	})

	it('See the title on the viewer header', function() {
		cy.get('body > .viewer .modal-header__name').should('contain', 'image2.jpg')
	})

	it('Should have rendered the previous video and the next image', function() {
		// There are buttons to navigate to the previous and next image
		cy.get('body > .viewer button.prev').should('be.visible')
		cy.get('body > .viewer button.next').should('be.visible')
		// The previous and the next image
		cy.get('body > .viewer .modal-container img').should('have.length', 2)
		// The next video
		cy.get('body > .viewer .modal-container video').should('have.length', 1)
	})

	it('Should make the previous and the next slides hidden for assistive technologies', function() {
		// Cypress doesn't respect aria-hidden in should.be.visible
		cy.get('body > .viewer .modal-container .viewer__file:not(.viewer__file--active) video')
			.parents('[aria-hidden="true"]')
			.should('exist')
		cy.get('body > .viewer .modal-container .viewer__file:not(.viewer__file--active) video')
			.parents('[inert]')
			.should('exist')
		cy.get('body > .viewer .modal-container .viewer__file:not(.viewer__file--active) img')
			.parents('[aria-hidden="true"]')
			.should('exist')
		cy.get('body > .viewer .modal-container .viewer__file:not(.viewer__file--active) img')
			.parents('[inert]')
			.should('exist')
	})

	it('Should make vido controls on the next slide not focusable', function() {
		cy.get('body > .viewer .modal-container .viewer__file:not(.viewer__file--active):has(video) button')
			.first()
			.focus()
		cy.get('body > .viewer .modal-container .viewer__file:not(.viewer__file--active):has(video) button')
			.first()
			.should('not.have.focus')
	})
})
