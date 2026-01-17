/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import AdminSettingsMailServer from './views/AdminSettingsMailServer.vue'

const app = new Vue(AdminSettingsMailServer)
app.$mount('#vue-admin-settings-mail')
