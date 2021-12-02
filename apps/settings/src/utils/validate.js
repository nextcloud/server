/**
 * @copyright 2021, Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/*
 * Frontend validators, less strict than backend validators
 *
 * TODO add nice validation errors for Profile page settings modal
 */

import { VALIDATE_EMAIL_REGEX } from '../constants/AccountPropertyConstants'

/**
 * Validate the string input
 *
 * Generic validator just to check that input is not an empty string*
 *
 * @param {string} input the input
 * @return {boolean}
 */
export function validateStringInput(input) {
	return input !== ''
}

/**
 * Validate the email input
 *
 * Compliant with PHP core FILTER_VALIDATE_EMAIL validator*
 *
 * Reference implementation https://github.com/mpyw/FILTER_VALIDATE_EMAIL.js/blob/71e62ca48841d2246a1b531e7e84f5a01f15e615/src/index.ts*
 *
 * @param {string} input the input
 * @return {boolean}
 */
export function validateEmail(input) {
	return typeof input === 'string'
		&& VALIDATE_EMAIL_REGEX.test(input)
		&& input.slice(-1) !== '\n'
		&& input.length <= 320
		&& encodeURIComponent(input).replace(/%../g, 'x').length <= 320
}

/**
 * Validate the language input
 *
 * @param {object} input the input
 * @return {boolean}
 */
export function validateLanguage(input) {
	return input.code !== ''
		&& input.name !== ''
		&& input.name !== undefined
}

/**
 * Validate boolean input
 *
 * @param {boolean} input the input
 * @return {boolean}
 */
export function validateBoolean(input) {
	return typeof input === 'boolean'
}
