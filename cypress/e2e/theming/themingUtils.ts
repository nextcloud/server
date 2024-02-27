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

const defaultNextcloudBlue = '#0082c9'
export const defaultPrimary = '#00679e'
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
	if (defaultSelectButton.length === 0) {
		return false
	}

	const defaultOptionBackground = defaultSelectButton.css('background-image')
	const colorPickerOptionColor = defaultSelectButton.css('background-color')
	const isNextcloudBlue = colord(colorPickerOptionColor).isEqual('#0082c9')

	const isValidBackgroundImage = !expectedBackground
		? defaultOptionBackground === 'none'
		: defaultOptionBackground.includes(expectedBackground)

	console.debug({ colorPickerOptionColor: colord(colorPickerOptionColor).toHex(), expectedColor, isValidBackgroundImage, isNextcloudBlue })

	return isValidBackgroundImage && (
		colord(colorPickerOptionColor).isEqual(expectedColor)
		// we replace nextcloud blue with the the default rpimary (apps/theming/lib/Themes/DefaultTheme.php line 76)
		|| (isNextcloudBlue && colord(expectedColor).isEqual(defaultPrimary))
	)
}

export const pickRandomColor = function(): Cypress.Chainable<string> {
	// Pick one of the first 8 options
	const randColour = Math.floor(Math.random() * 8)

	const colorPreviewSelector = '[data-user-theming-background-color],[data-admin-theming-setting-primary-color]'

	let oldColor = ''
	cy.get(colorPreviewSelector).then(($el) => {
		oldColor = $el.css('background-color')
	})

	// Open picker
	cy.contains('button', 'Change color').click()

	// Click on random color
	cy.get('.color-picker__simple-color-circle').eq(randColour).click()

	// Wait for color change
	cy.waitUntil(() => Cypress.$(colorPreviewSelector).css('background-color') !== oldColor)

	// Get the selected color from the color preview block
	return cy.get(colorPreviewSelector).then(($el) => $el.css('background-color'))
}
