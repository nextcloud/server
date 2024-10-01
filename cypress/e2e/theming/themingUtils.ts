/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { colord } from 'colord'

export const defaultPrimary = '#00679e'
export const defaultBackground = 'jenna-kim-the-globe.webp'

/**
 * Check if a CSS variable is set to a specific color
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
 * @param {string} expectedColor the expected primary color
 * @param {string|null} expectedBackground the expected background
 * @param {string|null} expectedBackgroundColor the expected background color (null to ignore)
 */
export function validateBodyThemingCss(expectedColor = defaultPrimary, expectedBackground: string|null = defaultBackground, expectedBackgroundColor: string|null = defaultPrimary) {
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
 * @param element JQuery element to check
 * @param color expected color
 */
export function expectBackgroundColor(element: JQuery<HTMLElement>, color: string) {
	expect(colord(element.css('background-color')).toHex()).equal(colord(color).toHex())
}

/**
 * Validate the user theming default select option css
 *
 * @param {string} expectedColor the expected color
 * @param {string} expectedBackground the expected background
 */
export const validateUserThemingDefaultCss = function(expectedColor = defaultPrimary, expectedBackground: string|null = defaultBackground) {
	const defaultSelectButton = Cypress.$('[data-user-theming-background-default]')
	if (defaultSelectButton.length === 0) {
		return false
	}

	const backgroundImage = defaultSelectButton.css('background-image')
	const backgroundColor = defaultSelectButton.css('background-color')

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

export const pickRandomColor = function(context: string, index?: number): Cypress.Chainable<string> {
	// Pick one of the first 8 options
	const randColour = index ?? Math.floor(Math.random() * 8)

	const colorPreviewSelector = `${context} [data-admin-theming-setting-color]`

	let oldColor = ''
	cy.get(colorPreviewSelector).then(($el) => {
		oldColor = $el.css('background-color')
	})

	// Open picker
	cy.get(`${context} [data-admin-theming-setting-color-picker]`).scrollIntoView()
	cy.get(`${context} [data-admin-theming-setting-color-picker]`).click({ force: true })

	// Click on random color
	cy.get('.color-picker__simple-color-circle').eq(randColour).click()

	// Wait for color change
	cy.waitUntil(() => Cypress.$(colorPreviewSelector).css('background-color') !== oldColor)

	// Get the selected color from the color preview block
	return cy.get(colorPreviewSelector).then(($el) => $el.css('background-color'))
}
