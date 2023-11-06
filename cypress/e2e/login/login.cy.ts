import type { User } from '@nextcloud/cypress'
import { getNextcloudUserMenu, getNextcloudUserMenuToggle } from '../../support/commonUtils'

describe('Login', () => {
	let user: User
	let disabledUser: User

	after(() => cy.deleteUser(user))
	before(() => {
		// disable brute force protection
		cy.runOccCommand('config:system:set auth.bruteforce.protection.enabled --value false --type bool')
		cy.createRandomUser().then(($user) => {
			user = $user
		})
		cy.createRandomUser().then(($user) => {
			disabledUser = $user
			cy.runOccCommand(`user:disable '${disabledUser.userId}'`)
		})
	})

	beforeEach(() => {
		cy.logout()
	})

	it('log in with valid user and password', () => {
		// Given I visit the Home page
		cy.visit('/')
		// I see the login page
		cy.get('form[name="login"]').should('be.visible')
		// I log in with a valid user
		cy.get('form[name="login"]').within(() => {
			cy.get('input[name="user"]').type(user.userId)
			cy.get('input[name="password"]').type(user.password)
			cy.contains('button[data-login-form-submit]', 'Log in').click()
		})

		// see that the login is done
		cy.get('[data-login-form-submit]').if().should('not.contain', 'Logging in')

		// Then I see that the current page is the Files app
		cy.url().should('match', /apps\/dashboard(\/|$)/)
	})

	it('try to log in with valid user and invalid password', () => {
		// Given I visit the Home page
		cy.visit('/')
		// I see the login page
		cy.get('form[name="login"]').should('be.visible')
		// I log in with a valid user but invalid password
		cy.get('form[name="login"]').within(() => {
			cy.get('input[name="user"]').type(user.userId)
			cy.get('input[name="password"]').type(`${user.password}--wrong`)
			cy.contains('button', 'Log in').click()
		})

		// see that the login is done
		cy.get('[data-login-form-submit]').if().should('not.contain', 'Logging in')

		// Then I see that the current page is the Login page
		cy.url().should('match', /\/login/)
		// And I see that a wrong password message is shown
		cy.get('form[name="login"]').then(($el) => expect($el.text()).to.match(/Wrong.+password/i))
		cy.get('input[name="password"]:invalid').should('exist')
	})

	it('try to log in with valid user and invalid password', () => {
		// Given I visit the Home page
		cy.visit('/')
		// I see the login page
		cy.get('form[name="login"]').should('be.visible')
		// I log in with a valid user but invalid password
		cy.get('form[name="login"]').within(() => {
			cy.get('input[name="user"]').type(user.userId)
			cy.get('input[name="password"]').type(`${user.password}--wrong`)
			cy.contains('button', 'Log in').click()
		})

		// see that the login is done
		cy.get('[data-login-form-submit]').if().should('not.contain', 'Logging in')

		// Then I see that the current page is the Login page
		cy.url().should('match', /\/login/)
		// And I see that a wrong password message is shown
		cy.get('form[name="login"]').then(($el) => expect($el.text()).to.match(/Wrong.+password/i).and.to.match(/Wrong.+username/))
		cy.get('input[name="password"]:invalid').should('exist')
	})

	it('try to log in with invalid user', () => {
		// Given I visit the Home page
		cy.visit('/')
		// I see the login page
		cy.get('form[name="login"]').should('be.visible')
		// I log in with an invalid user but valid password
		cy.get('form[name="login"]').within(() => {
			cy.get('input[name="user"]').type(`${user.userId}--wrong`)
			cy.get('input[name="password"]').type(user.password)
			cy.contains('button', 'Log in').click()
		})

		// see that the login is done
		cy.get('[data-login-form-submit]').if().should('not.contain', 'Logging in')

		// Then I see that the current page is the Login page
		cy.url().should('match', /\/login/)
		// And I see that a wrong password message is shown
		cy.get('form[name="login"]').then(($el) => expect($el.text()).to.match(/Wrong.+password/i).and.to.match(/Wrong.+username/))
		cy.get('input[name="password"]:invalid').should('exist')
	})

	it('try to log in as disabled user', () => {
		// Given I visit the Home page
		cy.visit('/')
		// I see the login page
		cy.get('form[name="login"]').should('be.visible')
		// When I log in with user disabledUser and password
		cy.get('form[name="login"]').within(() => {
			cy.get('input[name="user"]').type(disabledUser.userId)
			cy.get('input[name="password"]').type(disabledUser.password)
			cy.contains('button', 'Log in').click()
		})

		// see that the login is done
		cy.get('[data-login-form-submit]').if().should('not.contain', 'Logging in')

		// Then I see that the current page is the Login page
		cy.url().should('match', /\/login/)
		// And I see that the disabled user message is shown
		cy.get('form[name="login"]').then(($el) => expect($el.text()).to.match(/User.+disabled/i))
		cy.get('input[name="password"]:invalid').should('exist')
	})

	it('try to logout', () => {
		cy.login(user)

		// Given I visit the Home page
		cy.visit('/')
		// I see the dashboard
		cy.url().should('match', /apps\/dashboard(\/|$)/)

		// When click logout
		getNextcloudUserMenuToggle().should('exist').click()
		getNextcloudUserMenu().contains('a', 'Log out').click()

		// Then I see that the current page is the Login page
		cy.url().should('match', /\/login/)
	})
})
