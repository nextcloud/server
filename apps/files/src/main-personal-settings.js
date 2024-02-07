/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import PersonalSettings from './components/PersonalSettings.vue'

Vue.prototype.t = t

if (!window.TESTING) {
	const View = Vue.extend(PersonalSettings)
	new View().$mount('#files-personal-settings')
}
