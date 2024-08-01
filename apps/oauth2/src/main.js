/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import App from './App.vue'
import { loadState } from '@nextcloud/initial-state'

Vue.prototype.t = t
Vue.prototype.OC = OC

const clients = loadState('oauth2', 'clients')

const View = Vue.extend(App)
const oauth = new View({
	propsData: {
		clients,
	},
})
oauth.$mount('#oauth2')
