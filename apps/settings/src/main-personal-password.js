/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'

import PasswordSection from './components/PasswordSection.vue'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'

Vue.prototype.t = t
Vue.prototype.n = n

export default new Vue({
	el: '#security-password',
	name: 'main-personal-password',
	render: h => h(PasswordSection),
})
