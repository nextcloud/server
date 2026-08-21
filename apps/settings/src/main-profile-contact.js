/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import { translate as t } from '@nextcloud/l10n'
import Vue from 'vue'
import ProfileContactSettings from './views/ProfileContactSettings.vue'

__webpack_nonce__ = getCSPNonce()

Vue.mixin({
	methods: {
		t,
	},
})

const app = new Vue(ProfileContactSettings)
app.$mount('#profile-contact-settings')
