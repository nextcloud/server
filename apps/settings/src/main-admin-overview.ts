/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import AdminSettingsSetupChecks from './views/AdminSettingsSetupChecks.vue'

export default new Vue({
	name: 'AdminSettingsSetupChecks',
	el: '#vue-admin-settings-setup-checks',
	render: (h) => h(AdminSettingsSetupChecks),
})
