/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright 2020 Kirill Dmitriev <dk1a@protonmail.com>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author 2020 Kirill Dmitriev <dk1a@protonmail.com>
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
 *
 */

import { getRequestToken } from '@nextcloud/auth'
import { generateFilePath } from '@nextcloud/router'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import Vue from 'vue'

import ContactsMenu from './views/ContactsMenu.vue'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(getRequestToken())

// eslint-disable-next-line camelcase
__webpack_public_path__ = generateFilePath('core', '', 'js/')

Vue.mixin({
	methods: {
		t,
		n,
	},
})

export default new Vue({
	el: '#contacts-menu',
	// eslint-disable-next-line vue/match-component-file-name
	name: 'ContactsMenuRoot',
	render: h => h(ContactsMenu),
})
