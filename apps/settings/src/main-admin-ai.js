/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getCSPNonce } from '@nextcloud/auth'
import Vue from 'vue'
import ArtificialIntelligence from './components/AdminAI.vue'

__webpack_nonce__ = getCSPNonce()

Vue.prototype.t = t

const View = Vue.extend(ArtificialIntelligence)
new View().$mount('#ai-settings')
