/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import AdminSettingsPreviews from './views/AdminSettingsPreviews.vue'

export default new Vue({
	name: 'AdminSettingsPreviewsSection',
	el: '#vue-admin-settings-previews',
	render: (h) => h(AdminSettingsPreviews),
})
