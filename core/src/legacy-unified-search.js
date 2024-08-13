/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getLoggerBuilder } from '@nextcloud/logger'
import { getCSPNonce } from '@nextcloud/auth'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import Vue from 'vue'

import UnifiedSearch from './views/LegacyUnifiedSearch.vue'

// eslint-disable-next-line camelcase
__webpack_nonce__ = getCSPNonce()

const logger = getLoggerBuilder()
	.setApp('unified-search')
	.detectUser()
	.build()

Vue.mixin({
	data() {
		return {
			logger,
		}
	},
	methods: {
		t,
		n,
	},
})

export default new Vue({
	el: '#unified-search',
	// eslint-disable-next-line vue/match-component-file-name
	name: 'UnifiedSearchRoot',
	render: h => h(UnifiedSearch),
})
