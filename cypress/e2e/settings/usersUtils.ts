/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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

/**
 * Assert that `element` does not exist or is not visible
 *
 * Useful in cases such as when NcModal is opened/closed rapidly
 */
export function assertNotExistOrNotVisible(element: JQuery<HTMLElement>) {
	const doesNotExist = element.length === 0
	const isNotVisible = !element.is(':visible')

	expect(doesNotExist || isNotVisible, 'does not exist or is not visible').to.be.true
}
