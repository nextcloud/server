/**
 * @copyright 2019 Julius Härtl <jus@bitgrid.net>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import $ from 'jquery'

$.prototype.tooltip = (function(tooltip) {
	return function(config) {
		try {
			return tooltip.call(this, config)
		} catch (ex) {
			if (ex instanceof TypeError && config === 'destroy') {
				if (window.TESTING === undefined) {
					OC.debug && console.debug('Deprecated call $.tooltip(\'destroy\') has been deprecated and should be removed')
				}
				return tooltip.call(this, 'dispose')
			}
			if (ex instanceof TypeError && config === 'fixTitle') {
				if (window.TESTING === undefined) {
					OC.debug && console.debug('Deprecated call $.tooltip(\'fixTitle\') has been deprecated and should be removed')
				}
				return tooltip.call(this, '_fixTitle')
			}
		}
	}
})($.prototype.tooltip)
