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
	return cy.window().then((win) => {
		const guestBackgroundColor = getComputedStyle(win.document.body).backgroundColor
		const guestBackgroundImage = getComputedStyle(win.document.body).backgroundImage
		
		const isValidBackgroundColor = colord(guestBackgroundColor).isEqual(expectedColor)
		const isValidBackgroundImage = !expectedBackground
			? guestBackgroundImage === 'none'
			: guestBackgroundImage.includes(expectedBackground)

		console.debug({ guestBackgroundColor: colord(guestBackgroundColor).toHex(), guestBackgroundImage, expectedColor, expectedBackground, isValidBackgroundColor, isValidBackgroundImage })

		return isValidBackgroundColor && isValidBackgroundImage
	})
}

/**
 * Validate the user theming default select option css
 *
 * @param {string} expectedColor the expected color
 * @param {string} expectedBackground the expected background
 */
export const validateUserThemingDefaultCss = function(expectedColor = defaultPrimary, expectedBackground: string|null = defaultBackground) {
	return cy.window().then((win) => {
		const defaultSelectButton = win.document.querySelector('[data-user-theming-background-default]')
		const customColorSelectButton = win.document.querySelector('[data-user-theming-background-color]')
		if (!defaultSelectButton || !customColorSelectButton) {
			return false
		}

		const defaultOptionBackground = getComputedStyle(defaultSelectButton).backgroundImage
		const defaultOptionBorderColor = getComputedStyle(defaultSelectButton).borderColor
		const colorPickerOptionColor = getComputedStyle(customColorSelectButton).backgroundColor

		const isValidBackgroundImage = !expectedBackground
			? defaultOptionBackground === 'none'
			: defaultOptionBackground.includes(expectedBackground)
		
		console.debug(colord(defaultOptionBorderColor).toHex(), colord(colorPickerOptionColor).toHex(), expectedColor, isValidBackgroundImage)

		return isValidBackgroundImage
			&& colord(defaultOptionBorderColor).isEqual(expectedColor)
			&& colord(colorPickerOptionColor).isEqual(expectedColor)
	})
}

export const pickRandomColor = function(pickerSelector: string): Cypress.Chainable<string> {
	// Pick one of the first 8 options
	const randColour = Math.floor(Math.random() * 8)

	// Open picker
	cy.get(pickerSelector).click()

	// Return selected colour
	return cy.get(pickerSelector).get('.color-picker__simple-color-circle').eq(randColour)
		.click().then(colorElement => {
			const selectedColor = colorElement.css('background-color')
			return selectedColor
		})
}
