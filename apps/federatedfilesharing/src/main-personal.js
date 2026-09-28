/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import Vue from 'vue'
import { getCSPNonce } from '@nextcloud/auth'
import { translate as t } from '@nextcloud/l10n'

import PersonalSettings from './components/PersonalSettings.vue'

__webpack_nonce__ = getCSPNonce()

Vue.mixin({
	methods: {
		t,
	},
})

const PersonalSettingsView = Vue.extend(PersonalSettings)
new PersonalSettingsView().$mount('#vue-personal-federated')
