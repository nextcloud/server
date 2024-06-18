/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import { translate as t } from '@nextcloud/l10n'

import AdminSettings from './components/AdminSettings.vue'

Vue.mixin({
	methods: {
		t,
	},
})

const AdminSettingsView = Vue.extend(AdminSettings)
new AdminSettingsView().$mount('#vue-admin-sharebymail')
