/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import Vue from 'vue'
import AdminSettingsSharing from './views/AdminSettingsSharing.vue'

export default new Vue({
	name: 'AdminSettingsSharingSection',
	el: '#vue-admin-settings-sharing',
	render: (h) => h(AdminSettingsSharing),
})
