/*
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

let token = document.getElementsByTagName('head')[0].getAttribute('data-requesttoken');
const observers = []

/**
 * @return {string}
 */
export const getToken = () => token

/**
 * @param {Function} observer
 * @return {number}
 */
export const subscribe = observer => observers.push(observer)

/**
 * @param {String} newToken
 */
export const setToken = newToken => {
	token = newToken

	observers.forEach(o => o(token))
}
