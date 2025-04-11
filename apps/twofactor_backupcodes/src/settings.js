/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import Vue from 'vue'

import PersonalSettings from './views/PersonalSettings.vue'
import store from './store.js'

Vue.prototype.t = t

const initialState = loadState('twofactor_backupcodes', 'state')
store.replaceState(initialState)

const View = Vue.extend(PersonalSettings)
new View({
	store,
}).$mount('#twofactor-backupcodes-settings')
