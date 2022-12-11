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

/**
 * Validate the current page body css variables
 *
 * @param {string} expectedColor the expected color
 * @param {string|null} expectedBackground the expected background
 */
export const validateBodyThemingCss = function(expectedColor = '#0082c9', expectedBackground: string|null = 'kamil-porembinski-clouds.jpg') {
	return cy.window().then((win) => {
		const guestBackgroundColor = getComputedStyle(win.document.body).backgroundColor
		const guestBackgroundImage = getComputedStyle(win.document.body).backgroundImage
		
		const isValidBackgroundImage = expectedBackground === null
			? guestBackgroundImage === 'none'
			: guestBackgroundImage.includes(expectedBackground)

		return colord(guestBackgroundColor).isEqual(expectedColor)
			&& isValidBackgroundImage
	})
}

/**
 * Validate the user theming default select option css
 *
 * @param {string} expectedColor the expected color
 * @param {string} expectedBackground the expected background
 */
export const validateUserThemingDefaultCss = function(expectedColor = '#0082c9', expectedBackground: string|null = 'kamil-porembinski-clouds.jpg') {
	return cy.window().then((win) => {
		const defaultSelectButton = win.document.querySelector('[data-user-theming-background-default]')
		const customColorSelectButton = win.document.querySelector('[data-user-theming-background-color]')
		if (!defaultSelectButton || !customColorSelectButton) {
			return false
		}

		const defaultOptionBackground = getComputedStyle(defaultSelectButton).backgroundImage
		const defaultOptionBorderColor = getComputedStyle(defaultSelectButton).borderColor
		const colorPickerOptionColor = getComputedStyle(customColorSelectButton).backgroundColor

		const isValidBackgroundImage = expectedBackground === null
			? defaultOptionBackground === 'none'
			: defaultOptionBackground.includes(expectedBackground)

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
