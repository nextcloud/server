/**
 * Copyright (c) 2016
 *
 * @author Gary Kim <gary@garykim.dev>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
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

/* eslint-disable */
import escapeHTML from 'escape-html'

(function(OC) {
	/**
	 * @namespace
	 */
	OC.SystemTags = {
		/**
		 *
		 * @param {OC.SystemTags.SystemTagModel|Object|String} tag
		 * @returns {jQuery}
		 */
		getDescriptiveTag: function(tag) {
			if (_.isUndefined(tag.name) && !_.isUndefined(tag.toJSON)) {
				tag = tag.toJSON()
			}

			if (_.isUndefined(tag.name)) {
				return $('<span>').addClass('non-existing-tag').text(
					t('core', 'Non-existing tag #{tag}', {
						tag: tag
					})
				)
			}

			var $span = $('<span>')
			$span.append(escapeHTML(tag.name))

			var scope
			if (!tag.userAssignable) {
				scope = t('core', 'restricted')
			}
			if (!tag.userVisible) {
				// invisible also implicitly means not assignable
				scope = t('core', 'invisible')
			}
			if (scope) {
				$span.append($('<em>').text(' (' + scope + ')'))
			}
			return $span
		}
	}
})(OC)
