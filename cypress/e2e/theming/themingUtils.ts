/**
 * @copyright Copyright (c) 2022 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import { colord } from 'colord'

export const defaultPrimary = '#0082c9'
export const defaultAccessiblePrimary = '#006aa3'
export const defaultBackground = 'kamil-porembinski-clouds.jpg'

/**
 * Validate the current page body css variables
 *
 * @param {string} expectedColor the expected color
 * @param {string|null} expectedBackground the expected background
 */
export const validateBodyThemingCss = function(expectedColor = defaultPrimary, expectedBackground: string|null = defaultBackground) {
	// We must use `Cypress.$` here as any assertions (get is an assertion) is not allowed in wait-until's check function, see documentation
	const guestBackgroundColor = Cypress.$('body').css('background-color')
	const guestBackgroundImage = Cypress.$('body').css('background-image')

	const isValidBackgroundColor = colord(guestBackgroundColor).isEqual(expectedColor)
	const isValidBackgroundImage = !expectedBackground
		? guestBackgroundImage === 'none'
		: guestBackgroundImage.includes(expectedBackground)

	console.debug({ guestBackgroundColor: colord(guestBackgroundColor).toHex(), guestBackgroundImage, expectedColor, expectedBackground, isValidBackgroundColor, isValidBackgroundImage })

	return isValidBackgroundColor && isValidBackgroundImage
}

/**
 * Validate the user theming default select option css
 *
 * @param {string} expectedColor the expected color
 * @param {string} expectedBackground the expected background
 */
export const validateUserThemingDefaultCss = function(expectedColor = defaultPrimary, expectedBackground: string|null = defaultBackground) {
	const defaultSelectButton = Cypress.$('[data-user-theming-background-default]')
	const customColorSelectButton = Cypress.$('[data-user-theming-background-color]')
	if (defaultSelectButton.length === 0 || customColorSelectButton.length === 0) {
		return false
	}

	const defaultOptionBackground = defaultSelectButton.css('background-image')
	const colorPickerOptionColor = customColorSelectButton.css('background-color')

	const isValidBackgroundImage = !expectedBackground
		? defaultOptionBackground === 'none'
		: defaultOptionBackground.includes(expectedBackground)

	console.debug({ colorPickerOptionColor: colord(colorPickerOptionColor).toHex(), expectedColor, isValidBackgroundImage })

	return isValidBackgroundImage && colord(colorPickerOptionColor).isEqual(expectedColor)
}

export const pickRandomColor = function(pickerSelector: string): Cypress.Chainable<string> {
	// Pick one of the first 8 options
	const randColour = Math.floor(Math.random() * 8)

	// Open picker
	cy.get(pickerSelector).click()

	// Return selected colour
	return cy.get(pickerSelector).get('.color-picker__simple-color-circle').eq(randColour).then(($el) => {
		$el.trigger('click')
		return $el.css('background-color')
	})
}
