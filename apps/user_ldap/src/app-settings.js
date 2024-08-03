/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { translate } from '@nextcloud/l10n'
import Vue from 'vue'

import AppSettings from './AppSettings.vue'

Vue.prototype.t = translate
export default new Vue({
	el: '#user_ldap-app-settings',
	// eslint-disable-next-line vue/match-component-file-name
	name: 'AppSettings',
	render: h => h(AppSettings),
})
