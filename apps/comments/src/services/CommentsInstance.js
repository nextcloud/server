/**
 * @copyright Copyright (c) 2020 John Molakvoæ <skjnldsv@protonmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import { getRequestToken } from '@nextcloud/auth'
import Vue from 'vue'
import CommentsApp from '../views/Comments.vue'
import logger from '../logger.js'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(getRequestToken())

// Add translates functions
Vue.mixin({
	data() {
		return {
			logger,
		}
	},
	methods: {
		t,
		n,
	},
})

export default class CommentInstance {

	/**
	 * Initialize a new Comments instance for the desired type
	 *
	 * @param {string} commentsType the comments endpoint type
	 * @param  {object} options the vue options (propsData, parent, el...)
	 */
	constructor(commentsType = 'files', options) {
		// Add comments type as a global mixin
		Vue.mixin({
			data() {
				return {
					commentsType,
				}
			},
		})

		// Init Comments component
		const View = Vue.extend(CommentsApp)
		return new View(options)
	}

}
