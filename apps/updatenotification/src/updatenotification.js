/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { translate, translatePlural } from '@nextcloud/l10n'

import Vue from 'vue'
import Root from './components/UpdateNotification.vue'

Vue.mixin({
	methods: {
		t(app, text, vars, count, options) {
			return translate(app, text, vars, count, options)
		},
		n(app, textSingular, textPlural, count, vars, options) {
			return translatePlural(app, textSingular, textPlural, count, vars, options)
		},
	},
})

// eslint-disable-next-line no-new
new Vue({
	el: '#updatenotification',
	render: h => h(Root),
})
