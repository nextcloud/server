/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'

import ArtificialIntelligence from './components/AdminAI.vue'

Vue.prototype.t = t

// Not used here but required for legacy templates
window.OC = window.OC || {}
window.OC.Settings = window.OC.Settings || {}

const View = Vue.extend(ArtificialIntelligence)
new View().$mount('#ai-settings')
