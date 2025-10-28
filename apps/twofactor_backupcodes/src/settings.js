/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia, PiniaVuePlugin } from 'pinia'
import Vue from 'vue'
import PersonalSettings from './views/PersonalSettings.vue'

Vue.prototype.t = t
Vue.use(PiniaVuePlugin)

const pinia = createPinia()
const View = Vue.extend(PersonalSettings)
const app = new View({
	pinia,
})

app.$mount('#twofactor-backupcodes-settings')
