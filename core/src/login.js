/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// eslint-disable-next-line import/no-unresolved, n/no-missing-import
import 'vite/modulepreload-polyfill'

import Vue from 'vue'

import LoginView from './views/Login.vue'
import Nextcloud from './mixins/Nextcloud.js'

Vue.mixin(Nextcloud)

const View = Vue.extend(LoginView)
new View().$mount('#login')
