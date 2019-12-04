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

// TODO: import Util directly: https://github.com/nextcloud/server/pull/13957
import OC from '../OC/index'

/**
 * Format an UNIX timestamp to a human understandable format
 * @param {number} timestamp UNIX timestamp
 * @returns {string} Human readable format
 * @deprecated 16.0.0 use OC.Util.formatDate instead
 */
export default function formatDate(timestamp) {
	console.warn('formatDate is deprecated, use OC.Util.formatDate instead')
	return OC.Util.formatDate(timestamp)
}
