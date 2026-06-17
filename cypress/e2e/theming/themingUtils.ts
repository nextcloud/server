/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { colord } from 'colord'

export const defaultPrimary = '#00679e'
export const defaultBackground = 'jo-myoung-hee-fluid.webp'

/**
 * Check if a CSS variable is set to a specific color
 *
 * @param variable Variable to check
 * @param expectedColor Color that is expected
 */
export function validateCSSVariable(variable: string, expectedColor: string) {
	const value = window.getComputedStyle(Cypress.$('body').get(0)).getPropertyValue(variable)
	console.debug(`${variable}, is: ${colord(value).toHex()} expected: ${expectedColor}`)
	return colord(value).isEqual(expectedColor)
}

/**
 * Validate the current page body css variables
 *
 * @param expectedColor the expected primary color
 * @param expectedBackground the expected background
 * @param expectedBackgroundColor the expected background color (null to ignore)
 */
export function validateBodyThemingCss(expectedColor = defaultPrimary, expectedBackground: string | null = defaultBackground, expectedBackgroundColor: string | null = defaultPrimary) {
	// We must use `Cypress.$` here as any assertions (get is an assertion) is not allowed in wait-until's check function, see documentation
	const guestBackgroundColor = Cypress.$('body').css('background-color')
	const guestBackgroundImage = Cypress.$('body').css('background-image')

	const isValidBackgroundColor = expectedBackgroundColor === null || colord(guestBackgroundColor).isEqual(expectedBackgroundColor)
	const isValidBackgroundImage = !expectedBackground
		? guestBackgroundImage === 'none'
		: guestBackgroundImage.includes(expectedBackground)

	console.debug({
		isValidBackgroundColor,
		isValidBackgroundImage,
		guestBackgroundColor: colord(guestBackgroundColor).toHex(),
		guestBackgroundImage,
	})

	return isValidBackgroundColor && isValidBackgroundImage && validateCSSVariable('--color-primary', expectedColor)
}

/**
 * Check background color of element
 *
 * @param element JQuery element to check
 * @param color expected color
 */
export function expectBackgroundColor(element: JQuery<HTMLElement>, color: string) {
	expect(colord(element.css('background-color')).toHex()).equal(colord(color).toHex())
}

/**
 * Validate the user theming default select option css
 *
 * @param expectedColor the expected color
 * @param expectedBackground the expected background
 */
export function validateUserThemingDefaultCss(expectedColor = defaultPrimary, expectedBackground: string | null = defaultBackground) {
	const backgroundImage = Cypress.$('body').css('background-image')
	const backgroundColor = Cypress.$('body').css('background-color')

	const isValidBackgroundImage = !expectedBackground
		? (backgroundImage === 'none' || Cypress.$('body').css('background-image') === 'none')
		: backgroundImage.includes(expectedBackground)

	console.debug({
		colorPickerOptionColor: colord(backgroundColor).toHex(),
		expectedColor,
		isValidBackgroundImage,
		backgroundImage,
	})

	return isValidBackgroundImage && colord(backgroundColor).isEqual(expectedColor)
}

/**
 * @param trigger - The color picker trigger
 * @param index - The color index to pick, if not provided a random one will be picked
 */
export function pickColor(trigger: Cypress.Chainable<JQuery>, index?: number): Cypress.Chainable<string> {
	// Pick one of the first 8 options
	const randColour = index ?? Math.floor(Math.random() * 8)

	let oldColor = ''
	trigger.as('trigger').then(($el) => {
		oldColor = $el.css('background-color')
	})

	cy.get('@trigger').scrollIntoView()
	cy.get('@trigger').click({ force: true })

	// Click on random color
	cy.get('.color-picker__simple-color-circle').eq(randColour).click()

	// Wait for color change
	cy.get('@trigger')
		.should(($el) => $el.css('background-color') !== oldColor)

	cy.findByRole('button', { name: /Choose/i }).click()

	// Get the selected color from the color preview block
	return cy.get('@trigger').then(($el) => $el.css('background-color'))
}
