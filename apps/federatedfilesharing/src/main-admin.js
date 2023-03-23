/**
 * @copyright 2022 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import Vue from 'vue'
import { getRequestToken } from '@nextcloud/auth'
import { translate as t } from '@nextcloud/l10n'
import '@nextcloud/dialogs/dist/index.css'
import { loadState } from '@nextcloud/initial-state'

import AdminSettings from './components/AdminSettings.vue'

__webpack_nonce__ = btoa(getRequestToken())

Vue.mixin({
	methods: {
		t,
	},
})

const internalOnly = loadState('federatedfilesharing', 'internalOnly', false)

if (!internalOnly) {
	const AdminSettingsView = Vue.extend(AdminSettings)
	new AdminSettingsView().$mount('#vue-admin-federated')
}
