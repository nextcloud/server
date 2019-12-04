/**
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

// TODO: import Util directly: https://github.com/nextcloud/server/pull/13957
import OC from '../OC/index'

/**
 * Takes an absolute timestamp and return a string with a human-friendly relative date
 *
 * @param {number} timestamp A Unix timestamp
 * @deprecated use OC.Util.relativeModifiedDate instead but beware the argument value
 * @returns {string}
 */
export default function relativeModifiedDate(timestamp) {
	console.warn('relativeModifiedDate is deprecated, use OC.Util.relativeModifiedDate instead')
	/*
	 Were multiplying by 1000 to bring the timestamp back to its original value
	 per https://github.com/owncloud/core/pull/10647#discussion_r16790315
	  */
	return OC.Util.relativeModifiedDate(timestamp * 1000)
}
