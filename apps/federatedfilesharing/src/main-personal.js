/**
 * SPDX-FileLicenseText: 2022 Carl Schwan <carl@carlschwan.eu>
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

import PersonalSettings from './components/PersonalSettings.vue'

__webpack_nonce__ = btoa(getRequestToken())

Vue.mixin({
	methods: {
		t,
	},
})

const PersonalSettingsView = Vue.extend(PersonalSettings)
new PersonalSettingsView().$mount('#vue-personal-federated')
