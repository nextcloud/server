/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import { translate as t } from '@nextcloud/l10n'
import Vue from 'vue'

import logger from './logger.js'
import RecommendedApps from './components/setup/RecommendedApps.vue'

// eslint-disable-next-line camelcase
__webpack_nonce__ = getCSPNonce()

Vue.mixin({
	methods: {
		t,
	},
})

const View = Vue.extend(RecommendedApps)
new View().$mount('#recommended-apps')

logger.debug('recommended apps view rendered')
