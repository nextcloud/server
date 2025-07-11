/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const themesToTest = ['light', 'dark', 'light-highcontrast', 'dark-highcontrast']

const testCases = {
	'Main text': {
		foregroundColors: [
			'color-main-text',
			// 'color-text-light', deprecated
			// 'color-text-lighter', deprecated
			'color-text-maxcontrast',
		],
		backgroundColors: [
			'color-main-background',
			'color-background-hover',
			'color-background-dark',
			// 'color-background-darker', this should only be used for elements not for text
		],
	},
	'blurred background': {
		foregroundColors: [
			'color-main-text',
			'color-text-maxcontrast-blur',
		],
		backgroundColors: [
			'color-main-background-blur',
		],
	},
	Primary: {
		foregroundColors: [
			'color-primary-text',
		],
		backgroundColors: [
			// 'color-primary-default', this should only be used for elements not for text!
			// 'color-primary-hover', this should only be used for elements and not for text!
			'color-primary',
		],
	},
	'Primary light': {
		foregroundColors: [
			'color-primary-light-text',
		],
		backgroundColors: [
			'color-primary-light',
			'color-primary-light-hover',
		],
	},
	'Primary element': {
		foregroundColors: [
			'color-primary-element-text',
			'color-primary-element-text-dark',
		],
		backgroundColors: [
			'color-primary-element',
			'color-primary-element-hover',
		],
	},
	'Primary element light': {
		foregroundColors: [
			'color-primary-element-light-text',
		],
		backgroundColors: [
			'color-primary-element-light',
			'color-primary-element-light-hover',
		],
	},
	'Servity information texts': {
		foregroundColors: [
			'color-error-text',
			'color-warning-text',
			'color-success-text',
			'color-info-text',
		],
		backgroundColors: [
			'color-main-background',
			'color-background-hover',
			'color-main-background-blur',
		],
	},
}

/**
 * Create a wrapper element with color and background set
 *
 * @param foreground The foreground color (css variable without leading --)
 * @param background The background color
 */
function createTestCase(foreground: string, background: string) {
	const wrapper = document.createElement('div')
	wrapper.style.padding = '14px'
	wrapper.style.color = `var(--${foreground})`
	wrapper.style.backgroundColor = `var(--${background})`
	if (background.includes('blur')) {
		wrapper.style.backdropFilter = 'var(--filter-background-blur)'
	}

	const testCase = document.createElement('div')
	testCase.innerText = `${foreground} ${background}`
	testCase.setAttribute('data-cy-testcase', '')

	wrapper.appendChild(testCase)
	return wrapper
}

describe('Accessibility of Nextcloud theming colors', () => {
	for (const theme of themesToTest) {
		context(`Theme: ${theme}`, () => {
			before(() => {
				cy.createRandomUser().then(($user) => {
					// set user theme
					cy.runOccCommand(`user:setting -- '${$user.userId}' theming enabled-themes '[\\"${theme}\\"]'`)
					cy.login($user)
					cy.visit('/')
					cy.injectAxe({ axeCorePath: 'node_modules/axe-core/axe.min.js' })
				})
			})

			beforeEach(() => {
				cy.document().then(doc => {
					// Unset background image and thus use background-color for testing blur background (images do not work with axe-core)
					doc.body.style.backgroundImage = 'unset'

					const root = doc.querySelector('#content')
					if (root === null) {
						throw new Error('No test root found')
					}
					root.innerHTML = ''
				})
			})

			for (const [name, { backgroundColors, foregroundColors }] of Object.entries(testCases)) {
				context(`Accessibility of CSS color variables for ${name}`, () => {
					for (const foreground of foregroundColors) {
						for (const background of backgroundColors) {
							it(`color contrast of ${foreground} on ${background}`, () => {
								cy.document().then(doc => {
									const element = createTestCase(foreground, background)
									const root = doc.querySelector('#content')
									// eslint-disable-next-line no-unused-expressions
									expect(root).not.to.be.undefined
									// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
									root!.appendChild(element)

									cy.checkA11y('[data-cy-testcase]', {
										runOnly: ['color-contrast'],
									})
								})
							})
						}
					}
				})
			}
		})
	}
})
