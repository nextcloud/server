/**
 * @copyright Copyright (c) 2020 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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

import { getRequestToken } from '@nextcloud/auth'
import { generateFilePath } from '@nextcloud/router'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import Vue from 'vue'

import UnifiedSearch from './views/UnifiedSearch.vue'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(getRequestToken())

// eslint-disable-next-line camelcase
__webpack_public_path__ = generateFilePath('core', '', 'js/')

// TODO: remove with nc22
if (!OCA.Search) {
	class Search {

		constructor() {
			console.warn('OCA.Search is deprecated. Please use the unified search API instead')
		}

	}
	OCA.Search = Search
}

Vue.mixin({
	methods: {
		t,
		n,
	},
})

export default new Vue({
	el: '#unified-search',
	// eslint-disable-next-line vue/match-component-file-name
	name: 'UnifiedSearchRoot',
	render: h => h(UnifiedSearch),
})
