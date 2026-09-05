/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import LoginView from './views/Login.vue'
// eslint-disable-next-line no-unused-vars
import OC from './OC/index.js' // TODO: Not needed but L10n breaks if removed

const View = Vue.extend(LoginView)
new View().$mount('#login')
