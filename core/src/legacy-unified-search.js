/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import Vue from 'vue'
import UnifiedSearch from './views/LegacyUnifiedSearch.vue'

__webpack_nonce__ = getCSPNonce()

export default new Vue({
	el: '#unified-search',
	name: 'UnifiedSearchRoot',
	render: (h) => h(UnifiedSearch),
})
